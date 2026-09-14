<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Model\Character;

/**
 * starloco_login.world_players (characters). Normal players use groupe = 0 by default or -1 after a group reset.
 */
final readonly class PlayerRepository
{
    private const string NORMAL_PLAYERS = 'groupe IN (-1, 0)';

    public function __construct(private Database $database)
    {
    }

    /** @return list<Character> */
    public function byAccount(int $accountId): array
    {
        return $this->characters('SELECT name, class, sexe, level, xp, alignement FROM world_players WHERE account = ? ORDER BY xp DESC', [$accountId]);
    }

    /** @return list<Character> */
    public function topByXp(int $limit): array
    {
        return $this->characters('SELECT name, class, sexe, level, xp, alignement FROM world_players WHERE ' . self::NORMAL_PLAYERS . ' ORDER BY xp DESC LIMIT ?', [$limit]);
    }

    /** @return list<Character> */
    public function topByHonor(int $limit): array
    {
        return $this->characters('SELECT name, class, sexe, level, alignement, honor FROM world_players WHERE ' . self::NORMAL_PLAYERS . ' ORDER BY honor DESC LIMIT ?', [$limit]);
    }

    /**
     * Top players with their account's "show my position" preference.
     *
     * @return list<Character>
     */
    public function podium(int $limit = 3): array
    {
        return $this->characters(
            'SELECT p.name, p.class, p.sexe, p.level, p.xp, p.map, a.showOrHidePos FROM world_players p JOIN world_accounts a ON a.guid = p.account WHERE p.' . self::NORMAL_PLAYERS . ' ORDER BY p.xp DESC LIMIT ?',
            [$limit],
        );
    }

    /** @return list<Character> */
    public function wanted(int $limit = 5): array
    {
        return $this->characters('SELECT name, class, sexe, level, map, logged, deshonor FROM world_players WHERE ' . self::NORMAL_PLAYERS . ' AND deshonor > 0 ORDER BY deshonor DESC LIMIT ?', [$limit]);
    }

    /**
     * Job experience of the characters having job $jobId, by character name.
     * world_players.jobs format: "jobId,xp;jobId,xp".
     *
     * @return array<string, array<int, int>> name => [jobId => xp] (all the character's jobs)
     */
    public function jobsOfCharactersWithJob(int $jobId): array
    {
        $query = $this->database->login()->prepare('SELECT name, jobs FROM world_players WHERE ' . self::NORMAL_PLAYERS . " AND CONCAT(';', jobs) LIKE ?");
        $query->execute(['%;' . $jobId . ',%']);

        $result = [];
        foreach ($query->fetchAll() as $row) {
            $jobs = [];
            foreach (explode(';', (string) $row->jobs) as $entry) {
                [$id, $xp] = array_pad(explode(',', $entry), 2, '0');
                if ($id !== '') {
                    $jobs[(int) $id] = (int) $xp;
                }
            }
            $result[(string) $row->name] = $jobs;
        }
        return $result;
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

    /**
     * @param list<int> $params
     * @return list<Character>
     */
    private function characters(string $sql, array $params): array
    {
        $query = $this->database->login()->prepare($sql);
        $query->execute($params);
        return array_map(Character::fromRow(...), $query->fetchAll());
    }
}
