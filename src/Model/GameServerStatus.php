<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** Game server card of the sidebar. */
final readonly class GameServerStatus
{
    public function __construct(
        public string $name,
        public bool $online,
        public int $players,
        public ?string $uptime,
    ) {
    }
}
