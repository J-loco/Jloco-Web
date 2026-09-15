<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** A row in a drop viewer group: the other side of the relation (item or monster) and its rates. */
final readonly class DropLine
{
    public function __construct(
        public string $name,
        public int $prospecting,
        public float $minRate,
        public float $maxRate,
    ) {
    }
}
