<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** Totals shown in the sidebar. */
final readonly class SidebarStats
{
    public function __construct(
        public int $accounts,
        public int $characters,
        public int $guilds,
        public int $objects,
    ) {
    }
}
