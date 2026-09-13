<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;

/** starloco_login.world_guilds */
final class GuildRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @return list<object{name: string, lvl: int, xp: int}> */
    public function top(int $limit = 50): array
    {
        $query = $this->database->login()->prepare('SELECT name, lvl, xp FROM world_guilds ORDER BY xp DESC LIMIT ?');
        $query->execute([$limit]);
        return $query->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM world_guilds')->fetchColumn();
    }
}
