<?php

declare(strict_types=1);

namespace StarLoco\Web\Migration;

use InvalidArgumentException;
use PDO;

/**
 * Creates (or updates) the least-privilege MariaDB account the portal connects with.
 *
 * The portal can read the login and game databases but only write the tables below. Keep this list
 * in sync with the repositories (src/Repository/) and security stores (src/Security/).
 */
final readonly class WebUserProvisioner
{
    /** Login database: table => privileges beyond SELECT. */
    private const array LOGIN_WRITES = [
        'world_accounts' => 'INSERT, UPDATE',                    // register, account page, votes, shop points
        'website_remember_tokens' => 'INSERT, DELETE',
        'website_auth_attempts' => 'INSERT, DELETE',
        'website_password_resets' => 'INSERT, DELETE',
        'website_users_votes' => 'INSERT, DELETE',
        'website_shop_objects_purchases' => 'INSERT',
        'website_shop_points_purchases' => 'INSERT',
        'website_timeline_news' => 'INSERT, DELETE',             // administration
        'client_rss_news' => 'INSERT, DELETE',                   // administration
    ];

    /** Every game database (main one and shop ones): table => privileges beyond SELECT. */
    private const array GAME_WRITES = [
        'gifts' => 'INSERT, UPDATE',                             // shop delivery
    ];

    public function __construct(private PDO $admin)
    {
    }

    /** @param list<string> $gameDatabases */
    public function provision(string $user, string $password, string $loginDatabase, array $gameDatabases): void
    {
        if (!preg_match('/^[A-Za-z0-9_]{1,32}$/', $user)) {
            throw new InvalidArgumentException('Invalid database user name: ' . $user);
        }
        if ($password === '') {
            throw new InvalidArgumentException('The portal database password must not be empty');
        }

        $account = "'$user'@'%'";
        $secret = $this->admin->quote($password);
        $this->admin->exec("CREATE USER IF NOT EXISTS $account IDENTIFIED BY $secret");
        $this->admin->exec("ALTER USER $account IDENTIFIED BY $secret");

        $this->grant($account, $loginDatabase, self::LOGIN_WRITES);
        foreach (array_unique($gameDatabases) as $gameDatabase) {
            $this->grant($account, $gameDatabase, self::GAME_WRITES);
        }
    }

    /** @param array<string, string> $writes */
    private function grant(string $account, string $database, array $writes): void
    {
        $db = self::identifier($database);
        $this->admin->exec("GRANT SELECT ON $db.* TO $account");
        foreach ($writes as $table => $privileges) {
            $this->admin->exec("GRANT $privileges ON $db." . self::identifier($table) . " TO $account");
        }
    }

    private static function identifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Invalid identifier: ' . $name);
        }
        return "`$name`";
    }
}
