<?php

declare(strict_types=1);

namespace StarLoco\Web\Security;

use StarLoco\Web\Config;
use StarLoco\Web\Database;
use StarLoco\Web\Http\Request;

/**
 * Brute-force protection: at most MAX_FAILURES failed attempts per (IP, action) per window.
 * Table: website_auth_attempts (migrations/002).
 */
final class Throttle
{
    public const LOGIN = 'login';
    public const PASSWORD_RESET = 'password_reset';
    public const SECRET_ANSWER = 'secret_answer';

    public const MAX_FAILURES = 5;
    public const WINDOW_MINUTES = 15;

    public function __construct(
        private readonly Database $database,
        private readonly Request $request,
        private readonly Config $config,
    ) {
    }

    public function isBlocked(string $action): bool
    {
        $query = $this->database->login()->prepare(
            'SELECT COUNT(*) FROM website_auth_attempts WHERE ip = ? AND action = ? AND attempted_at > NOW() - INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE'
        );
        $query->execute([$this->ip(), $action]);
        return (int) $query->fetchColumn() >= self::MAX_FAILURES;
    }

    public function recordFailure(string $action): void
    {
        $login = $this->database->login();
        $login->prepare('INSERT INTO website_auth_attempts (ip, action, attempted_at) VALUES (?, ?, NOW())')->execute([$this->ip(), $action]);
        if (random_int(1, 50) === 1) {
            $login->exec('DELETE FROM website_auth_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY');
        }
    }

    public function clear(string $action): void
    {
        $this->database->login()->prepare('DELETE FROM website_auth_attempts WHERE ip = ? AND action = ?')->execute([$this->ip(), $action]);
    }

    public static function message(): string
    {
        return 'Trop de tentatives. Réessaie dans ' . self::WINDOW_MINUTES . ' minutes.';
    }

    private function ip(): string
    {
        return $this->request->clientIp($this->config->trustCloudflare);
    }
}
