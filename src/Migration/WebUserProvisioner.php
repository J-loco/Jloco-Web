<?php

declare(strict_types=1);

namespace StarLoco\Web\Migration;

use InvalidArgumentException;
use PDO;

/**
 * Creates (or updates) the least-privilege MariaDB account the portal connects with.
 *
 * The portal can read both databases but only write the tables below. Keep this list in sync
 * with the queries in pages/ and include/ (later: src/Repository/).
 */
final class WebUserProvisioner
{
    /** Table => privileges beyond SELECT, per database ("login" / "game"). */
    private const WRITES = [
        'login' => [
            'world_accounts' => 'INSERT, UPDATE',                    // register, profile, vote, shop points
            'website_remember_tokens' => 'INSERT, DELETE',
            'website_auth_attempts' => 'INSERT, DELETE',
            'website_users_votes' => 'INSERT, DELETE',
            'website_shop_objects_purchases' => 'INSERT',
            'website_shop_points_purchases' => 'INSERT',
            'website_timeline_news' => 'INSERT, DELETE',             // administration
            'client_rss_news' => 'INSERT, DELETE',                   // administration
        ],
        'game' => [
            'gifts' => 'INSERT, UPDATE',                             // shop delivery
        ],
    ];

    public function __construct(private readonly PDO $admin)
    {
    }

    /** @param array{login: string, game: string} $databases */
    public function provision(string $user, string $password, array $databases): void
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

        foreach ($databases as $key => $database) {
            $db = self::identifier($database);
            $this->admin->exec("GRANT SELECT ON $db.* TO $account");
            foreach (self::WRITES[$key] as $table => $privileges) {
                $this->admin->exec("GRANT $privileges ON $db." . self::identifier($table) . " TO $account");
            }
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
