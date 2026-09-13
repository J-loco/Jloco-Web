<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Model\WorldServer;

/** starloco_login.world_servers, website_users_votes and a few totals. */
final readonly class ServerRepository
{
    public function __construct(private Database $database)
    {
    }

    public function find(int $id): ?WorldServer
    {
        $query = $this->database->login()->prepare('SELECT id, name, uptime, population FROM world_servers WHERE id = ?');
        $query->execute([$id]);
        $row = $query->fetch();
        return $row ? WorldServer::fromRow($row) : null;
    }

    public function countOnlineAccounts(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM world_accounts WHERE logged = 1')->fetchColumn();
    }

    public function countObjects(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM world_objects')->fetchColumn();
    }

    /** Unix time of the last vote from this IP, 0 if none. */
    public function lastVoteFromIp(string $ip): int
    {
        $query = $this->database->login()->prepare('SELECT MAX(date) FROM website_users_votes WHERE ip = ?');
        $query->execute([$ip]);
        return (int) $query->fetchColumn();
    }

    public function recordVoteFromIp(string $ip, int $time): void
    {
        $login = $this->database->login();
        $login->prepare('DELETE FROM website_users_votes WHERE ip = ?')->execute([$ip]);
        $login->prepare('INSERT INTO website_users_votes (ip, date) VALUES (?, ?)')->execute([$ip, $time]);
    }
}
