<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** A row of jloco_login.world_servers. */
final readonly class WorldServer
{
    public function __construct(
        public int $id,
        public string $name,
        /** Start time in milliseconds, 0 when unknown. */
        public int $startedAtMs,
        public int $population,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self((int) $row->id, (string) $row->name, (int) $row->uptime, (int) $row->population);
    }
}
