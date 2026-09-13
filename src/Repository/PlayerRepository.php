<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;

/**
 * starloco_login.world_players (characters). Rankings only include normal players (groupe = -1).
 */
final class PlayerRepository
{
    private const NORMAL_PLAYERS = 'groupe = -1';

    public function __construct(private readonly Database $database)
    {
    }

    /** @return list<object> */
    public function byAccount(int $accountId): array
    {
        $query = $this->database->login()->prepare('SELECT name, class, sexe, level, xp, alignement FROM world_players WHERE account = ? ORDER BY xp DESC');
        $query->execute([$accountId]);
        return $query->fetchAll();
    }

    /** @return list<object> */
    public function topByXp(int $limit): array
    {
        return $this->list('SELECT name, class, sexe, level, xp, alignement FROM world_players WHERE ' . self::NORMAL_PLAYERS . ' ORDER BY xp DESC LIMIT ?', [$limit]);
    }

    /** @return list<object> */
    public function topByHonor(int $limit): array
    {
        return $this->list('SELECT name, class, sexe, level, alignement, honor FROM world_players WHERE ' . self::NORMAL_PLAYERS . ' ORDER BY honor DESC LIMIT ?', [$limit]);
    }

    /** Top players with their account's "show my position" preference. @return list<object> */
    public function podium(int $limit = 3): array
    {
        return $this->list(
            'SELECT p.name, p.class, p.sexe, p.level, p.map, a.showOrHidePos FROM world_players p JOIN world_accounts a ON a.guid = p.account WHERE p.' . self::NORMAL_PLAYERS . ' ORDER BY p.xp DESC LIMIT ?',
            [$limit]
        );
    }

    /** @return list<object> */
    public function wanted(int $limit = 5): array
    {
        return $this->list('SELECT name, class, sexe, level, map, logged, deshonor FROM world_players WHERE ' . self::NORMAL_PLAYERS . ' AND deshonor > 0 ORDER BY deshonor DESC LIMIT ?', [$limit]);
    }

    /** Characters having job $jobId. world_players.jobs format: "jobId,xp;jobId,xp". @return list<object{name: string, jobs: string}> */
    public function withJob(int $jobId): array
    {
        return $this->list("SELECT name, jobs FROM world_players WHERE CONCAT(';', jobs) LIKE ?", ['%;' . $jobId . ',%']);
    }

    public function countOnline(?int $serverId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM world_players WHERE logged = 1' . ($serverId !== null ? ' AND server = ?' : '');
        $query = $this->database->login()->prepare($sql);
        $query->execute($serverId !== null ? [$serverId] : []);
        return (int) $query->fetchColumn();
    }

    public function count(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM world_players')->fetchColumn();
    }

    /** @return list<object> */
    private function list(string $sql, array $params): array
    {
        $query = $this->database->login()->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }
}
