<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Model\Drop;
use StarLoco\Web\Model\MapPosition;

/**
 * Static data from the game database (starloco_game): jobs, drops, maps; plus sub-area names
 * (login database) and shop gifts.
 */
final readonly class GameRepository
{
    public function __construct(private Database $database)
    {
    }

    /**
     * Jobs players can learn (those using a tool).
     *
     * @return array<int, string> id => name
     */
    public function jobs(): array
    {
        $jobs = [];
        foreach ($this->database->game()->query("SELECT id, name FROM jobs_data WHERE tools != '' ORDER BY name")->fetchAll() as $job) {
            $jobs[(int) $job->id] = (string) $job->name;
        }
        return $jobs;
    }

    /**
     * Drops whose monster or item name contains $term.
     *
     * @param 'monster'|'item' $by
     * @return list<Drop>
     */
    public function searchDrops(string $by, string $term, int $limit = 500): array
    {
        $column = $by === 'monster' ? 'm.name' : 'i.name';
        $query = $this->database->game()->prepare(
            "SELECT m.id AS monster_id, m.name AS monster_name, i.id AS item_id, i.name AS item_name, d.ceil, d.percentGrade1, d.percentGrade5
             FROM drops d JOIN monsters m ON m.id = d.monsterId JOIN item_template i ON i.id = d.objectId
             WHERE LOWER($column) LIKE ? ORDER BY $column, d.percentGrade5 DESC LIMIT ?",
        );
        $query->execute(['%' . mb_strtolower($term) . '%', $limit]);
        return array_map(Drop::fromRow(...), $query->fetchAll());
    }

    public function mapPosition(int $mapId): ?MapPosition
    {
        $query = $this->database->game()->prepare('SELECT mappos FROM maps WHERE id = ?');
        $query->execute([$mapId]);
        $mappos = $query->fetchColumn();
        return $mappos === false ? null : MapPosition::fromMappos((string) $mappos);
    }

    /** Sub-area names live in the login database. */
    public function subAreaName(int $subAreaId): ?string
    {
        $query = $this->database->login()->prepare('SELECT name FROM world_base_sub_areas WHERE id = ?');
        $query->execute([$subAreaId]);
        $name = $query->fetchColumn();
        return $name === false ? null : (string) $name;
    }

    /**
     * Queues an item for the account in the given game database; the game server delivers it at
     * next login (same "template,quantity,jp" format as Account.addGift in StarLoco-Game).
     */
    public function addGift(string $gameDatabase, int $accountId, int $template, int $quantity, bool $maxStats): void
    {
        $gift = $template . ',' . $quantity . ',' . ($maxStats ? 1 : 0);
        $game = $gameDatabase === $this->database->gameDatabaseName() ? $this->database->game() : $this->database->connect($gameDatabase);
        $game->prepare("INSERT IGNORE INTO gifts (id, objects) VALUES (?, '')")->execute([$accountId]);
        $game->prepare("UPDATE gifts SET objects = IF(objects = '', ?, CONCAT(objects, ';', ?)) WHERE id = ?")->execute([$gift, $gift, $accountId]);
    }
}
