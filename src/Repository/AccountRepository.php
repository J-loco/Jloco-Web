<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Model\Account;
use StarLoco\Web\Model\Credentials;
use StarLoco\Web\Model\Voter;
use StarLoco\Web\Support\Text;

/**
 * starloco_login.world_accounts (shared with StarLoco-Login and StarLoco-Game).
 *
 * Its text columns are latin1: a lookup value latin1 cannot represent cannot match, and comparing
 * it would raise a collation error, so such lookups return "not found" without querying.
 */
final readonly class AccountRepository
{
    /** Columns of Account; the password hash is only read by findCredentials(). */
    private const string PROFILE = 'guid, account, pseudo, email, points, votes, totalVotes, heurevote, dateRegister, lastConnectionDate, question, showOrHide, showOrHidePos';

    /** Privacy flags an account can toggle, by public name. */
    public const array PRIVACY_FLAGS = ['armory' => 'showOrHide', 'position' => 'showOrHidePos'];

    public function __construct(private Database $database)
    {
    }

    public function find(int $id): ?Account
    {
        $query = $this->database->login()->prepare('SELECT ' . self::PROFILE . ' FROM world_accounts WHERE guid = ?');
        $query->execute([$id]);
        $row = $query->fetch();
        return $row ? Account::fromRow($row) : null;
    }

    public function findByName(string $name): ?Account
    {
        if (!Text::fitsLatin1($name)) {
            return null;
        }
        $query = $this->database->login()->prepare('SELECT ' . self::PROFILE . ' FROM world_accounts WHERE account = ?');
        $query->execute([$name]);
        $row = $query->fetch();
        return $row ? Account::fromRow($row) : null;
    }

    public function findCredentials(string $name): ?Credentials
    {
        if (!Text::fitsLatin1($name)) {
            return null;
        }
        $query = $this->database->login()->prepare('SELECT guid, pass FROM world_accounts WHERE account = ?');
        $query->execute([$name]);
        $row = $query->fetch();
        return $row ? new Credentials((int) $row->guid, (string) $row->pass) : null;
    }

    public function exists(string $name): bool
    {
        if (!Text::fitsLatin1($name)) {
            return false;
        }
        $query = $this->database->login()->prepare('SELECT 1 FROM world_accounts WHERE account = ?');
        $query->execute([$name]);
        return $query->fetchColumn() !== false;
    }

    public function create(string $name, string $passwordHash, string $email, string $question, string $answer): int
    {
        $login = $this->database->login();
        $login
            ->prepare('INSERT INTO world_accounts (account, pass, email, question, reponse, dateRegister) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$name, $passwordHash, $email, $question, $answer, date('d/m/y')]);
        return (int) $login->lastInsertId();
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
            'UPDATE world_accounts SET votes = votes + 1, totalVotes = totalVotes + 1, points = points + ?, heurevote = ? WHERE guid = ? AND heurevote <= ?',
        );
        $query->execute([$points, $now, $id, $now - $cooldown]);
        return $query->rowCount() === 1;
    }

    /** @return list<Voter> */
    public function topVoters(int $limit = 50): array
    {
        $query = $this->database->login()->prepare('SELECT pseudo, votes FROM world_accounts WHERE votes > 0 ORDER BY votes DESC LIMIT ?');
        $query->execute([$limit]);
        return array_map(Voter::fromRow(...), $query->fetchAll());
    }

    public function count(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM world_accounts')->fetchColumn();
    }
}
