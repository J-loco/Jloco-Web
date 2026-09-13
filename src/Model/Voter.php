<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** A ranked voter. Only the pseudo: account names are credentials. */
final readonly class Voter
{
    public function __construct(
        public ?string $pseudo,
        public int $votes,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self($row->pseudo !== null && $row->pseudo !== '' ? (string) $row->pseudo : null, (int) $row->votes);
    }
}
