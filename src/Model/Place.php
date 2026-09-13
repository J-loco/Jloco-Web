<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** Where a character was seen: sub-area name and coordinates. */
final readonly class Place
{
    public function __construct(
        public string $name,
        public int $x,
        public int $y,
    ) {
    }
}
