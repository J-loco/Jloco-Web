<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** A character with its place, when the place may be shown. */
final readonly class LocatedCharacter
{
    public function __construct(
        public Character $character,
        public ?Place $place,
    ) {
    }
}
