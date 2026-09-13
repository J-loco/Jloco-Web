<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Support\Text;

/**
 * starloco_login.world_accounts (shared with StarLoco-Login and StarLoco-Game).
 *
 * Its text columns are latin1: a lookup value latin1 cannot represent cannot match, and comparing
 * it would raise a collation error, so such lookups return "not found" without querying.
 */
final class AccountRepository
{
    /** Columns the portal reads; never select "pass" outside findCredentials(). */
    private const PROFILE = 'guid, account, pseudo, email, points, votes, totalVotes, heurevote, dateRegister, lastConnectionDate, question, showOrHide, showOrHidePos';

    /** Privacy flags an account can toggle, by public name. */
    public const PRIVACY_FLAGS = ['armory' => 'showOrHide', 'position' => 'showOrHidePos'];

    public function __construct(private readonly Database $database)
    {
    }

    public function find(int $id): ?object
    {
        $query = $this->database->login()->prepare('SELECT ' . self::PROFILE . ' FROM world_accounts WHERE guid = ?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function findByName(string $account): ?object
    {
        if (!Text::fitsLatin1($account)) {
            return null;
        }
        $query = $this->database->login()->prepare('SELECT ' . self::PROFILE . ' FROM world_accounts WHERE account = ?');
        $query->execute([$account]);
        return $query->fetch() ?: null;
    }

    /** @return object{guid: int, account: string, pass: string}|null */
    public function findCredentials(string $account): ?object
    {
        if (!Text::fitsLatin1($account)) {
            return null;
        }
        $query = $this->database->login()->prepare('SELECT guid, account, pass FROM world_accounts WHERE account = ?');
        $query->execute([$account]);
        return $query->fetch() ?: null;
    }

    public function exists(string $account): bool
    {
        if (!Text::fitsLatin1($account)) {
            return false;
        }
        $query = $this->database->login()->prepare('SELECT 1 FROM world_accounts WHERE account = ?');
        $query->execute([$account]);
        return $query->fetchColumn() !== false;
    }

    public function create(string $account, string $passwordHash, string $email, string $question, string $answer): void
    {
        $this->database->login()
            ->prepare('INSERT INTO world_accounts (account, pass, email, question, reponse, dateRegister) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$account, $passwordHash, $email, $question, $answer, date('d/m/y')]);
    }

    public function answerMatches(int $id, string $answer): bool
    {
        if (!Text::fitsLatin1($answer)) {
            return false;
        }
        $query = $this->database->login()->prepare('SELECT 1 FROM world_accounts WHERE guid = ? AND reponse = ?');
        $query->execute([$id, $answer]);
        return $query->fetchColumn() !== false;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->database->login()->prepare('UPDATE world_accounts SET pass = ? WHERE guid = ?')->execute([$passwordHash, $id]);
    }

    /** @param key-of<self::PRIVACY_FLAGS> $flag */
    public function togglePrivacy(int $id, string $flag): void
    {
        $column = self::PRIVACY_FLAGS[$flag];
        $this->database->login()->prepare("UPDATE world_accounts SET `$column` = 1 - `$column` WHERE guid = ?")->execute([$id]);
    }

    public function addPoints(int $id, int $points): void
    {
        $this->database->login()->prepare('UPDATE world_accounts SET points = points + ? WHERE guid = ?')->execute([$points, $id]);
    }

    /** Atomic: only debits when the balance is sufficient. Returns whether it did. */
    public function debitPoints(int $id, int $points): bool
    {
        $query = $this->database->login()->prepare('UPDATE world_accounts SET points = points - ? WHERE guid = ? AND points >= ?');
        $query->execute([$points, $id, $points]);
        return $query->rowCount() === 1;
    }

    /** Atomic: credits a vote only if the previous one is older than $cooldown seconds. */
    public function creditVote(int $id, int $points, int $now, int $cooldown): bool
    {
        $query = $this->database->login()->prepare(
            'UPDATE world_accounts SET votes = votes + 1, totalVotes = totalVotes + 1, points = points + ?, heurevote = ? WHERE guid = ? AND heurevote <= ?'
        );
        $query->execute([$points, $now, $id, $now - $cooldown]);
        return $query->rowCount() === 1;
    }

    /**
     * Account names are login credentials and must never be displayed: only the pseudo is returned.
     *
     * @return list<object{pseudo: ?string, votes: int}>
     */
    public function topVoters(int $limit = 50): array
    {
        $query = $this->database->login()->prepare('SELECT pseudo, votes FROM world_accounts WHERE votes > 0 ORDER BY votes DESC LIMIT ?');
        $query->execute([$limit]);
        return $query->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM world_accounts')->fetchColumn();
    }
}
