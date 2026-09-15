<?php

declare(strict_types=1);

namespace JLoco\Web\Service;

use JLoco\Web\Database;
use JLoco\Web\Http\Router;
use JLoco\Web\Repository\AccountRepository;
use JLoco\Web\Security\PasswordHasher;
use JLoco\Web\Security\Throttle;
use Throwable;

/**
 * Password reset by email link (when MAILER_DSN is set). The link carries "selector/token"; only a
 * SHA-256 of the token is stored (website_password_resets), valid TTL_MINUTES and single use.
 * Responses never reveal whether an account exists.
 */
final readonly class PasswordResetService
{
    public const int TTL_MINUTES = 60;

    public function __construct(
        private AccountRepository $accounts,
        private Database $database,
        private Mailer $mailer,
        private Router $router,
        private Throttle $throttle,
        private PasswordHasher $hasher,
    ) {
    }

    /**
     * Sends a reset link to the account's email, if the account exists and has one.
     * Returns false only when the visitor is rate-limited.
     */
    public function request(string $accountName): bool
    {
        if ($this->throttle->isBlocked(Throttle::PASSWORD_RESET)) {
            return false;
        }
        // Every request counts, so the form cannot be used to spam an inbox or enumerate accounts.
        $this->throttle->recordFailure(Throttle::PASSWORD_RESET);

        $account = $this->accounts->findByName($accountName);
        if ($account === null || $account->email === null || !filter_var($account->email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        $selector = bin2hex(random_bytes(12));
        $token = bin2hex(random_bytes(32));
        $login = $this->database->login();
        $login->prepare('DELETE FROM website_password_resets WHERE account = ? OR expires_at < NOW()')->execute([$account->id]);
        $login->prepare('INSERT INTO website_password_resets (selector, token_hash, account, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL ' . self::TTL_MINUTES . ' MINUTE)')
            ->execute([$selector, hash('sha256', $token), $account->id]);

        try {
            $this->mailer->send($account->email, 'Réinitialisation de ton mot de passe', 'password_reset', [
                'pseudo' => $account->pseudo,
                'link' => $this->router->url('password_reset_confirm', ['selector' => $selector, 'token' => $token]),
                'ttl' => self::TTL_MINUTES,
            ]);
        } catch (Throwable $e) {
            error_log('JLoco-Web: password reset email failed for account ' . $account->id . ': ' . $e->getMessage());
        }
        return true;
    }

    /** Account id of a valid, unexpired link; null otherwise. */
    public function accountForLink(string $selector, string $token): ?int
    {
        $query = $this->database->login()->prepare('SELECT token_hash, account FROM website_password_resets WHERE selector = ? AND expires_at > NOW()');
        $query->execute([$selector]);
        $row = $query->fetch();
        return $row && hash_equals((string) $row->token_hash, hash('sha256', $token)) ? (int) $row->account : null;
    }

    /** Sets the new password and consumes the link. Returns an error message, or null on success. */
    public function reset(string $selector, string $token, string $password, string $confirm): ?string
    {
        $accountId = $this->accountForLink($selector, $token);
        if ($accountId === null) {
            return 'Ce lien n\'est plus valide. Fais une nouvelle demande.';
        }
        if ($error = AuthService::passwordError($password, $confirm)) {
            return $error;
        }
        $this->accounts->updatePassword($accountId, $this->hasher->hash($password));
        $this->database->login()->prepare('DELETE FROM website_password_resets WHERE account = ?')->execute([$accountId]);
        $this->throttle->clear(Throttle::PASSWORD_RESET);
        return null;
    }
}
