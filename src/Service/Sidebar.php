<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use StarLoco\Web\Model\Character;
use StarLoco\Web\Model\LocatedCharacter;
use StarLoco\Web\Model\Place;
use StarLoco\Web\Model\SidebarData;
use StarLoco\Web\Model\SidebarStats;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Repository\GameRepository;
use StarLoco\Web\Repository\GuildRepository;
use StarLoco\Web\Repository\PlayerRepository;
use StarLoco\Web\Repository\ServerRepository;

/**
 * Data for the sidebar shown on most pages: server status, statistics, top players, wanted players.
 */
final class Sidebar
{
    private ?SidebarData $data = null;

    public function __construct(
        private readonly ServerStatus $status,
        private readonly AccountRepository $accounts,
        private readonly PlayerRepository $players,
        private readonly GuildRepository $guilds,
        private readonly ServerRepository $servers,
        private readonly GameRepository $game,
    ) {
    }

    public function data(): SidebarData
    {
        return $this->data ??= new SidebarData(
            loginOnline: $this->status->loginOnline(),
            loginPlayers: $this->players->countOnline(),
            game: $this->status->gameServer(),
            stats: new SidebarStats(
                accounts: $this->accounts->count(),
                characters: $this->players->count(),
                guilds: $this->guilds->count(),
                objects: $this->servers->countObjects(),
            ),
            // Podium: position only if the owner allows it. Wanted: position only while connected.
            podium: array_map(fn (Character $c): LocatedCharacter => $this->locate($c, $c->positionVisible), $this->players->podium(3)),
            wanted: array_map(fn (Character $c): LocatedCharacter => $this->locate($c, $c->online), $this->players->wanted(5)),
        );
    }

    private function locate(Character $character, bool $visible): LocatedCharacter
    {
        $place = null;
        if ($visible && ($position = $this->game->mapPosition($character->mapId)) !== null) {
            $name = $this->game->subAreaName($position->subAreaId);
            $place = $name !== null ? new Place($name, $position->x, $position->y) : null;
        }
        return new LocatedCharacter($character, $place);
    }
}
