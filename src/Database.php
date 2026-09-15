<?php

declare(strict_types=1);

namespace JLoco\Web;

use PDO;

/**
 * PDO connections to the login and game databases, opened on first use.
 */
final class Database
{
    private ?PDO $login = null;
    private ?PDO $game = null;

    /** @var array<string, PDO> other databases (extra shop game databases) */
    private array $others = [];

    public function __construct(private readonly Config $config)
    {
    }

    public function login(): PDO
    {
        return $this->login ??= $this->open($this->config->loginDbName);
    }

    public function game(): PDO
    {
        return $this->game ??= $this->open($this->config->gameDbName);
    }

    public function gameDatabaseName(): string
    {
        return $this->config->gameDbName;
    }

    /** A shared connection to any database of the server (e.g. another game database of the shop). */
    public function connect(string $database): PDO
    {
        return match ($database) {
            $this->config->loginDbName => $this->login(),
            $this->config->gameDbName => $this->game(),
            default => $this->others[$database] ??= $this->open($database),
        };
    }

    /** Opens a new connection; $user/$pass override the portal credentials (bin/migrate, tests). */
    public function open(string $database, ?string $user = null, ?string $pass = null): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->config->dbHost, $this->config->dbPort, $database);

        return new PDO($dsn, $user ?? $this->config->dbUser, $pass ?? $this->config->dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
