<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;

/**
 * Static and per-account data from the game database (starloco_game): jobs, drops, maps, gifts.
 */
final class GameRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /** Jobs players can learn (those using a tool). @return array<int, string> id => name */
    public function jobs(): array
    {
        $jobs = [];
        foreach ($this->database->game()->query("SELECT id, name FROM jobs_data WHERE tools != '' ORDER BY name")->fetchAll() as $job) {
            $jobs[(int) $job->id] = $job->name;
        }
        return $jobs;
    }

    /**
     * Drops whose monster or item name contains $term.
     *
     * @param 'monster'|'item' $by
     * @return list<object{monster_id: int, monster_name: string, item_id: int, item_name: string, ceil: int, percentGrade1: float, percentGrade5: float}>
     */
    public function searchDrops(string $by, string $term, int $limit = 500): array
    {
        $column = $by === 'monster' ? 'm.name' : 'i.name';
        $query = $this->database->game()->prepare(
            "SELECT m.id AS monster_id, m.name AS monster_name, i.id AS item_id, i.name AS item_name, d.ceil, d.percentGrade1, d.percentGrade5
             FROM drops d JOIN monsters m ON m.id = d.monsterId JOIN item_template i ON i.id = d.objectId
             WHERE LOWER($column) LIKE ? ORDER BY $column, d.percentGrade5 DESC LIMIT ?"
        );
        $query->execute(['%' . mb_strtolower($term) . '%', $limit]);
        return $query->fetchAll();
    }

    /** maps.mappos is "x,y,subAreaId". @return object{x: string, y: string, subArea: int}|null */
    public function mapPosition(int $mapId): ?object
    {
        $query = $this->database->game()->prepare('SELECT mappos FROM maps WHERE id = ?');
        $query->execute([$mapId]);
        $mappos = $query->fetchColumn();
        if ($mappos === false) {
            return null;
        }
        [$x, $y, $subArea] = array_pad(explode(',', (string) $mappos), 3, '0');
        return (object) ['x' => $x, 'y' => $y, 'subArea' => (int) $subArea];
    }

    /** Sub-area names live in the login database. */
    public function subAreaName(int $subAreaId): ?string
    {
        $query = $this->database->login()->prepare('SELECT name FROM world_base_sub_areas WHERE id = ?');
        $query->execute([$subAreaId]);
        $name = $query->fetchColumn();
        return $name === false ? null : (string) $name;
    }

    /** Queues an item for the account; the game server delivers it at next login (same format as Account.addGift). */
    public function addGift(int $accountId, int $template, int $quantity, bool $maxStats): void
    {
        $gift = $template . ',' . $quantity . ',' . ($maxStats ? 1 : 0);
        $game = $this->database->game();
        $game->prepare("INSERT IGNORE INTO gifts (id, objects) VALUES (?, '')")->execute([$accountId]);
        $game->prepare("UPDATE gifts SET objects = IF(objects = '', ?, CONCAT(objects, ';', ?)) WHERE id = ?")->execute([$gift, $gift, $accountId]);
    }
}
