<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** A row of jloco_login.world_guilds. */
final readonly class Guild
{
    public function __construct(
        public string $name,
        public int $level,
        public int $xp,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self((string) $row->name, (int) $row->lvl, (int) $row->xp);
    }
}
