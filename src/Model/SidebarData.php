<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** Everything the sidebar renders. */
final readonly class SidebarData
{
    public function __construct(
        public bool $loginOnline,
        public int $loginPlayers,
        public GameServerStatus $game,
        public SidebarStats $stats,
        /** @var list<LocatedCharacter> */
        public array $podium,
        /** @var list<LocatedCharacter> */
        public array $wanted,
    ) {
    }
}
