<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** One line of the drops table: a monster, an item it drops, and the rates from grade 1 to 5. */
final readonly class Drop
{
    public function __construct(
        public int $monsterId,
        public string $monsterName,
        public int $itemId,
        public string $itemName,
        /** Prospecting threshold, 0 when there is none. */
        public int $prospecting,
        public float $minRate,
        public float $maxRate,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            (int) $row->monster_id,
            (string) $row->monster_name,
            (int) $row->item_id,
            (string) $row->item_name,
            (int) $row->ceil,
            (float) $row->percentGrade1,
            (float) $row->percentGrade5,
        );
    }
}
