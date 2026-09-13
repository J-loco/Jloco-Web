<?php

declare(strict_types=1);

namespace StarLoco\Web\Security;

use StarLoco\Web\Database;
use StarLoco\Web\Http\Request;

/**
 * "Remember me" cookie "selector:validator". Only a SHA-256 of the validator is stored
 * (website_remember_tokens); tokens are single use and rotated on every restore.
 */
final class RememberMe
{
    private const COOKIE = 'starloco_remember';
    private const DAYS = 7;

    public function __construct(
        private readonly Database $database,
        private readonly Session $session,
        private readonly Request $request,
    ) {
    }

    public function issue(int $accountId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expires = time() + self::DAYS * 86400;

        $this->database->login()
            ->prepare('INSERT INTO website_remember_tokens (selector, validator_hash, account, expires_at) VALUES (?, ?, ?, FROM_UNIXTIME(?))')
            ->execute([$selector, hash('sha256', $validator), $accountId, $expires]);

        setcookie(self::COOKIE, $selector . ':' . $validator, $this->session->cookieOptions($expires));
    }

    /** Account id from a valid cookie (a fresh token is issued), or null. */
    public function restore(): ?int
    {
        $cookie = $this->request->cookie(self::COOKIE);
        if ($cookie === null || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $parts)) {
            return null;
        }
        [, $selector, $validator] = $parts;
        $login = $this->database->login();

        $query = $login->prepare('SELECT validator_hash, account FROM website_remember_tokens WHERE selector = ? AND expires_at > NOW()');
        $query->execute([$selector]);
        $token = $query->fetch();
        $query->closeCursor();

        $login->prepare('DELETE FROM website_remember_tokens WHERE selector = ?')->execute([$selector]);

        if (!$token || !hash_equals($token->validator_hash, hash('sha256', $validator))) {
            $this->clearCookie();
            return null;
        }

        $this->issue((int) $token->account);
        return (int) $token->account;
    }

    public function forget(): void
    {
        $cookie = $this->request->cookie(self::COOKIE);
        if ($cookie !== null && str_contains($cookie, ':')) {
            $this->database->login()->prepare('DELETE FROM website_remember_tokens WHERE selector = ?')->execute([explode(':', $cookie)[0]]);
        }
        $this->clearCookie();
    }

    private function clearCookie(): void
    {
        setcookie(self::COOKIE, '', $this->session->cookieOptions(1));
    }
}
