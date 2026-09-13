<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** Drop viewer result: all drops of one monster (or of one item). */
final readonly class DropGroup
{
    public function __construct(
        public string $name,
        /** @var list<DropLine> */
        public array $lines,
    ) {
    }
}
