<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Model\Guild;

/** starloco_login.world_guilds */
final readonly class GuildRepository
{
    public function __construct(private Database $database)
    {
    }

    /** @return list<Guild> */
    public function top(int $limit = 50): array
    {
        $query = $this->database->login()->prepare('SELECT name, lvl, xp FROM world_guilds ORDER BY xp DESC LIMIT ?');
        $query->execute([$limit]);
        return array_map(Guild::fromRow(...), $query->fetchAll());
    }

    public function count(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM world_guilds')->fetchColumn();
    }
}
