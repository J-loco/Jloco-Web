<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** World coordinates of a map (maps.mappos "x,y,subAreaId"). */
final readonly class MapPosition
{
    public function __construct(
        public int $x,
        public int $y,
        public int $subAreaId,
    ) {
    }

    public static function fromMappos(string $mappos): self
    {
        [$x, $y, $subArea] = array_pad(explode(',', $mappos), 3, '0');
        return new self((int) $x, (int) $y, (int) $subArea);
    }
}
