<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** A row of jloco_login.world_players. Columns a query does not select keep their default. */
final readonly class Character
{
    public function __construct(
        public string $name,
        public int $breed,
        public int $sex,
        public int $level,
        public int $xp = 0,
        public int $alignment = 0,
        public int $honor = 0,
        public int $dishonor = 0,
        public int $mapId = 0,
        public bool $online = false,
        /** The owner account's "show my position" preference (world_accounts.showOrHidePos). */
        public bool $positionVisible = false,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            name: (string) $row->name,
            breed: (int) $row->class,
            sex: (int) $row->sexe,
            level: (int) $row->level,
            xp: (int) ($row->xp ?? 0),
            alignment: (int) ($row->alignement ?? 0),
            honor: (int) ($row->honor ?? 0),
            dishonor: (int) ($row->deshonor ?? 0),
            mapId: (int) ($row->map ?? 0),
            online: (int) ($row->logged ?? 0) === 1,
            positionVisible: (bool) ($row->showOrHidePos ?? false),
        );
    }
}
