<?php

declare(strict_types=1);

namespace StarLoco\Web;

use StarLoco\Web\Security\PasswordHasher;

/**
 * Typed application settings, read once from the environment.
 *
 * Precedence: real environment variables (docker compose) > StarLoco-Web/.env (phpdotenv) > defaults.
 * Every variable is documented in .env.example.
 */
final readonly class Config
{
    /**
     * @param array<int, string> $shopServers shop server key (website_shop_objects.server) => game database name
     * @param PasswordHasher::SCHEME_* $passwordHashScheme
     */
    public function __construct(
        public string $appUrl,
        public bool $debug,
        public bool $trustCloudflare,
        public string $siteName,
        public int $adminAccountId,
        public string $dbHost,
        public int $dbPort,
        public string $dbUser,
        public string $dbPass,
        public string $loginDbName,
        public string $gameDbName,
        public string $loginServerHost,
        public int $loginServerPort,
        public string $gameServerHost,
        public int $gameServerPort,
        public int $gameServerId,
        public string $forumUrl,
        public string $forumRssUrl,
        public string $downloadUrl,
        public string $voteUrl,
        public int $votePoints,
        public string $dedipassPublicKey,
        public array $shopServers,
        public string $passwordHashScheme,
        public string $mailerDsn,
        public string $mailFrom,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $gameDbName = self::string('GAME_DB_NAME', 'starloco_game');

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
            gameDbName: $gameDbName,
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
            shopServers: self::parseShopServers(self::string('SHOP_SERVERS', '1:' . $gameDbName)),
            passwordHashScheme: self::string('PASSWORD_HASH_SCHEME', PasswordHasher::SCHEME_LEGACY) === PasswordHasher::SCHEME_PBKDF2
                ? PasswordHasher::SCHEME_PBKDF2
                : PasswordHasher::SCHEME_LEGACY,
            mailerDsn: self::string('MAILER_DSN', ''),
            mailFrom: self::string('MAIL_FROM', ''),
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

    /** Password reset by email is offered only when outgoing mail is configured. */
    public function mailEnabled(): bool
    {
        return $this->mailerDsn !== '' && $this->mailFrom !== '';
    }

    /**
     * "1:starloco_game,2:starloco_game_2" => [1 => 'starloco_game', 2 => 'starloco_game_2'].
     *
     * @return array<int, string>
     */
    public static function parseShopServers(string $value): array
    {
        $servers = [];
        foreach (array_filter(array_map(trim(...), explode(',', $value))) as $entry) {
            [$key, $database] = array_pad(explode(':', $entry, 2), 2, '');
            if (ctype_digit($key) && preg_match('/^[A-Za-z0-9_]+$/', $database)) {
                $servers[(int) $key] = $database;
            }
        }
        return $servers;
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
