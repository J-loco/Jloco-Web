<?php

declare(strict_types=1);

namespace StarLoco\Web;

/**
 * Typed application settings, read once from the environment.
 *
 * Precedence: real environment variables (docker compose) > StarLoco-Web/.env (phpdotenv) > defaults.
 * Every variable is documented in .env.example.
 */
final class Config
{
    public function __construct(
        public readonly string $appUrl,
        public readonly bool $debug,
        public readonly bool $trustCloudflare,
        public readonly string $siteName,
        public readonly int $adminAccountId,
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
        public readonly int $gameServerId,
        public readonly string $forumUrl,
        public readonly string $forumRssUrl,
        public readonly string $downloadUrl,
        public readonly string $voteUrl,
        public readonly int $votePoints,
        public readonly string $dedipassPublicKey,
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            appUrl: rtrim(self::string('APP_URL', 'http://127.0.0.1/dofus/'), '/') . '/',
            debug: self::bool('APP_DEBUG'),
            trustCloudflare: self::bool('TRUST_CLOUDFLARE'),
            siteName: self::string('SITE_NAME', 'StarLoco'),
            adminAccountId: self::int('ADMIN_ACCOUNT_ID', 1),
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
            gameServerId: self::int('GAME_SERVER_ID', 601),
            forumUrl: self::string('FORUM_URL', ''),
            forumRssUrl: self::string('FORUM_RSS_URL', ''),
            downloadUrl: self::string('DOWNLOAD_URL', ''),
            voteUrl: self::string('VOTE_URL', 'https://www.rpg-paradize.com/'),
            votePoints: self::int('VOTE_POINTS', 5),
            dedipassPublicKey: self::string('DEDIPASS_PUBLIC_KEY', ''),
        );
    }

    /** The URL path the site is served under, without trailing slash: "/dofus" (or "" at the root). */
    public function basePath(): string
    {
        return rtrim(parse_url($this->appUrl, PHP_URL_PATH) ?: '', '/');
    }

    public function isHttps(): bool
    {
        return str_starts_with($this->appUrl, 'https://');
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
