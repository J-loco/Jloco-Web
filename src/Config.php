<?php

declare(strict_types=1);

namespace StarLoco\Web;

/**
 * Typed application settings, read once from the environment.
 *
 * Precedence: real environment variables (docker compose) > StarLoco-Web/.env (phpdotenv) > defaults.
 */
final class Config
{
    public function __construct(
        public readonly string $appUrl,
        public readonly bool $debug,
        public readonly bool $trustCloudflare,
        public readonly string $dedipassPublicKey,
        public readonly string $dbHost,
        public readonly int $dbPort,
        public readonly string $dbUser,
        public readonly string $dbPass,
        public readonly string $loginDbName,
        public readonly string $gameDbName,
        public readonly string $loginServerHost,
        public readonly int $loginServerPort,
        public readonly string $gameServerHost,
        public readonly int $gameServerPort,
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            appUrl: rtrim(self::string('APP_URL', 'http://127.0.0.1/dofus/'), '/') . '/',
            debug: self::bool('APP_DEBUG'),
            trustCloudflare: self::bool('TRUST_CLOUDFLARE'),
            dedipassPublicKey: self::string('DEDIPASS_PUBLIC_KEY', ''),
            dbHost: self::string('DB_HOST', '127.0.0.1'),
            dbPort: self::int('DB_PORT', 3306),
            // "root" keeps installs that predate the starloco_web user working.
            dbUser: self::string('DB_USER', 'root'),
            dbPass: self::string('DB_PASS', ''),
            loginDbName: self::string('LOGIN_DB_NAME', 'starloco_login'),
            gameDbName: self::string('GAME_DB_NAME', 'starloco_game'),
            loginServerHost: self::string('LOGIN_HOST', '127.0.0.1'),
            loginServerPort: self::int('LOGIN_PORT', 450),
            gameServerHost: self::string('GAME_HOST', '127.0.0.1'),
            gameServerPort: self::int('GAME_PORT', 5555),
        );
    }

    /** The URL path the site is served under, e.g. "/dofus/". */
    public function basePath(): string
    {
        return parse_url($this->appUrl, PHP_URL_PATH) ?: '/';
    }

    private static function raw(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        return is_string($value) ? $value : null;
    }

    private static function string(string $key, string $default): string
    {
        return self::raw($key) ?? $default;
    }

    private static function int(string $key, int $default): int
    {
        $value = self::raw($key);
        return $value !== null && ctype_digit($value) ? (int) $value : $default;
    }

    private static function bool(string $key): bool
    {
        return in_array(strtolower(self::raw($key) ?? ''), ['1', 'true', 'yes', 'on'], true);
    }
}
