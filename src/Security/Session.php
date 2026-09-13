<?php

declare(strict_types=1);

namespace StarLoco\Web\Security;

use StarLoco\Web\Config;

/**
 * Hardened PHP session + one-shot flash messages.
 */
final readonly class Session
{
    private const string FLASH_KEY = '_flash';

    public function __construct(private Config $config)
    {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('STARLOCO_SESSID');
        $options = $this->cookieOptions(0);
        unset($options['expires']);
        session_set_cookie_params(['lifetime' => 0] + $options);
        session_start();
    }

    /**
     * Cookie attributes shared by the session and remember-me cookies.
     *
     * @return array{expires: int, path: string, secure: bool, httponly: bool, samesite: 'Lax'}
     */
    public function cookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => $this->config->basePath() . '/',
            'secure' => $this->config->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Returns the value and removes it. */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    /** New session id, keeping the data (call on privilege change). */
    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function clear(): void
    {
        $_SESSION = [];
        $this->regenerate();
    }

    /** @param 'success'|'info'|'warning'|'danger' $type */
    public function flash(string $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type: string, message: string}> */
    public function takeFlashes(): array
    {
        return $this->pull(self::FLASH_KEY, []);
    }
}
