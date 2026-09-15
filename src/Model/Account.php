<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

use DateTimeImmutable;

/** A row of jloco_login.world_accounts, as the portal sees it (never the password). */
final readonly class Account
{
    public function __construct(
        public int $id,
        /** Login name: a credential, never display it. */
        public string $name,
        public ?string $pseudo,
        public ?string $email,
        public int $points,
        public int $votes,
        public int $totalVotes,
        /** Unix time of the last credited vote (world_accounts.heurevote). */
        public int $lastVoteAt,
        /** world_accounts.dateRegister, stored as free text ("d/m/y"). */
        public ?string $registeredOn,
        public ?DateTimeImmutable $lastLoginAt,
        public string $question,
        public bool $visibleInArmory,
        public bool $positionVisible,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            id: (int) $row->guid,
            name: (string) $row->account,
            pseudo: $row->pseudo !== null && $row->pseudo !== '' ? (string) $row->pseudo : null,
            email: $row->email !== null ? (string) $row->email : null,
            points: (int) $row->points,
            votes: (int) $row->votes,
            totalVotes: (int) $row->totalVotes,
            lastVoteAt: (int) $row->heurevote,
            registeredOn: $row->dateRegister !== null && $row->dateRegister !== '' ? (string) $row->dateRegister : null,
            lastLoginAt: self::parseLegacyDate($row->lastConnectionDate),
            question: (string) $row->question,
            visibleInArmory: (bool) $row->showOrHide,
            positionVisible: (bool) $row->showOrHidePos,
        );
    }

    /** world_accounts.lastConnectionDate is written by the login server as "YYYY~MM~DD~HH~MM". */
    public static function parseLegacyDate(mixed $value): ?DateTimeImmutable
    {
        $parts = explode('~', (string) $value);
        if (count($parts) < 5) {
            return null;
        }
        [$year, $month, $day, $hour, $minute] = array_map(intval(...), array_slice($parts, 0, 5));
        return DateTimeImmutable::createFromFormat('Y-n-j G:i', sprintf('%d-%d-%d %d:%02d', $year, $month, $day, $hour, $minute)) ?: null;
    }
}
