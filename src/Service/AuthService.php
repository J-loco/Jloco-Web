<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use StarLoco\Web\Captcha;
use StarLoco\Web\Config;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Security\RememberMe;
use StarLoco\Web\Security\Session;
use StarLoco\Web\Security\Throttle;
use StarLoco\Web\Support\Text;

/**
 * Who is logged in, and how accounts log in, register and change passwords.
 */
final class AuthService
{
    private const SESSION_ACCOUNT = 'account_id';

    private ?object $account = null;
    private bool $loaded = false;

    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly Session $session,
        private readonly RememberMe $rememberMe,
        private readonly Throttle $throttle,
        private readonly Captcha $captcha,
        private readonly Config $config,
    ) {
    }

    /** Password format shared with StarLoco-Login; changing it requires changing the login server too. */
    public static function hashPassword(string $password): string
    {
        return hash('sha512', md5($password));
    }

    /** The logged-in account (profile columns), loaded once per request. */
    public function account(): ?object
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $id = $this->session->get(self::SESSION_ACCOUNT);
            $this->account = is_int($id) ? $this->accounts->find($id) : null;
            if (is_int($id) && $this->account === null) {
                $this->session->remove(self::SESSION_ACCOUNT); // account deleted meanwhile
            }
        }
        return $this->account;
    }

    public function accountId(): ?int
    {
        return $this->account() !== null ? (int) $this->account()->guid : null;
    }

    public function isAdmin(): bool
    {
        return $this->accountId() === $this->config->adminAccountId;
    }

    /** Returns an error message, or null when logged in. */
    public function attempt(string $username, string $password, bool $remember): ?string
    {
        if ($this->throttle->isBlocked(Throttle::LOGIN)) {
            return Throttle::message();
        }

        $account = $this->accounts->findCredentials($username);
        if ($account === null || !hash_equals((string) $account->pass, self::hashPassword($password))) {
            $this->throttle->recordFailure(Throttle::LOGIN);
            return 'Nom de compte ou mot de passe incorrect.';
        }

        $this->throttle->clear(Throttle::LOGIN);
        $this->loginAs((int) $account->guid);
        if ($remember) {
            $this->rememberMe->issue((int) $account->guid);
        }
        return null;
    }

    public function logout(): void
    {
        $this->rememberMe->forget();
        $this->session->clear();
        $this->account = null;
        $this->loaded = true;
    }

    /** Logs the visitor back in from a remember-me cookie, if any. */
    public function restoreFromCookie(): void
    {
        if ($this->session->get(self::SESSION_ACCOUNT) === null && ($id = $this->rememberMe->restore()) !== null) {
            $this->loginAs($id);
        }
    }

    /**
     * Validates and creates an account.
     *
     * @param array{username: string, email: string, password: string, password_confirm: string, question: string, answer: string, captcha: string, terms: bool} $input
     * @return array<string, string> field => error message; empty on success
     */
    public function register(array $input): array
    {
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $input['username'])) {
            $errors['username'] = 'De 3 à 30 caractères : lettres, chiffres, « - » et « _ ».';
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL) || strlen($input['email']) > 100) {
            $errors['email'] = 'Adresse email invalide.';
        }
        if (mb_strlen($input['password']) < 6 || mb_strlen($input['password']) > 50) {
            $errors['password'] = 'Entre 6 et 50 caractères.';
        } elseif (!hash_equals($input['password'], $input['password_confirm'])) {
            $errors['password_confirm'] = 'Les mots de passe ne sont pas identiques.';
        }
        foreach (['question' => 'La question', 'answer' => 'La réponse'] as $field => $label) {
            $length = mb_strlen(trim($input[$field]));
            if ($length < 2 || $length > 100) {
                $errors[$field] = $label . ' doit faire entre 2 et 100 caractères.';
            } elseif (!Text::fitsLatin1($input[$field])) {
                $errors[$field] = $label . ' contient des caractères non pris en charge (émojis, alphabets non latins).';
            }
        }
        if (!$input['terms']) {
            $errors['terms'] = 'Tu dois accepter les conditions d\'utilisation.';
        }
        // Always consumed, even when another field is invalid: the form comes back with a new image.
        if (!$this->captcha->verify($input['captcha'])) {
            $errors['captcha'] = 'Le code ne correspond pas à l\'image.';
        }
        if ($errors === [] && $this->accounts->exists($input['username'])) {
            $errors['username'] = 'Ce nom de compte est déjà pris.';
        }

        if ($errors === []) {
            $this->accounts->create($input['username'], self::hashPassword($input['password']), $input['email'], trim($input['question']), trim($input['answer']));
        }
        return $errors;
    }

    /** Password change from the account page, protected by the secret answer. Returns an error or null. */
    public function changePassword(int $accountId, string $answer, string $password, string $confirm): ?string
    {
        if ($this->throttle->isBlocked(Throttle::SECRET_ANSWER)) {
            return Throttle::message();
        }
        if (!$this->accounts->answerMatches($accountId, $answer)) {
            $this->throttle->recordFailure(Throttle::SECRET_ANSWER);
            return 'La réponse secrète est incorrecte.';
        }
        if (mb_strlen($password) < 6 || mb_strlen($password) > 50) {
            return 'Le mot de passe doit faire entre 6 et 50 caractères.';
        }
        if (!hash_equals($password, $confirm)) {
            return 'Les mots de passe ne sont pas identiques.';
        }
        $this->throttle->clear(Throttle::SECRET_ANSWER);
        $this->accounts->updatePassword($accountId, self::hashPassword($password));
        return null;
    }

    /**
     * Resets a forgotten password with the secret answer.
     *
     * @return array{error: ?string, password: ?string} the new random password on success
     */
    public function resetPassword(string $account, string $answer): array
    {
        if ($this->throttle->isBlocked(Throttle::PASSWORD_RESET)) {
            return ['error' => Throttle::message(), 'password' => null];
        }
        $row = $this->accounts->findByName($account);
        if ($row === null || !$this->accounts->answerMatches((int) $row->guid, $answer)) {
            $this->throttle->recordFailure(Throttle::PASSWORD_RESET);
            return ['error' => 'La réponse secrète est incorrecte.', 'password' => null];
        }

        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < 10; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $this->throttle->clear(Throttle::PASSWORD_RESET);
        $this->accounts->updatePassword((int) $row->guid, self::hashPassword($password));
        return ['error' => null, 'password' => $password];
    }

    private function loginAs(int $accountId): void
    {
        $this->session->regenerate();
        $this->session->set(self::SESSION_ACCOUNT, $accountId);
        $this->loaded = false;
    }
}
