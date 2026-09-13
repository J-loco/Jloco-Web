<?php

declare(strict_types=1);

namespace StarLoco\Web;

use PDO;

/**
 * PDO connections to the login and game databases, opened on first use.
 */
final class Database
{
    private ?PDO $login = null;
    private ?PDO $game = null;

    public function __construct(private readonly Config $config)
    {
    }

    public function login(): PDO
    {
        return $this->login ??= $this->connect($this->config->loginDbName);
    }

    public function game(): PDO
    {
        return $this->game ??= $this->connect($this->config->gameDbName);
    }

    /** Opens a new connection; $user/$pass override the portal credentials (used by bin/migrate). */
    public function connect(string $database, ?string $user = null, ?string $pass = null): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->config->dbHost, $this->config->dbPort, $database);

        return new PDO($dsn, $user ?? $this->config->dbUser, $pass ?? $this->config->dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
