<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

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
    private ?object $data = null;

    public function __construct(
        private readonly ServerStatus $status,
        private readonly AccountRepository $accounts,
        private readonly PlayerRepository $players,
        private readonly GuildRepository $guilds,
        private readonly ServerRepository $servers,
        private readonly GameRepository $game,
    ) {
    }

    public function data(): object
    {
        return $this->data ??= (object) [
            'login' => (object) ['online' => $this->status->loginOnline(), 'players' => $this->players->countOnline()],
            'game' => $this->status->gameServer(),
            'stats' => (object) [
                'accounts' => $this->accounts->count(),
                'characters' => $this->players->count(),
                'guilds' => $this->guilds->count(),
                'objects' => $this->servers->countObjects(),
            ],
            'podium' => array_map(fn (object $player) => $this->withPlace($player, (bool) $player->showOrHidePos), $this->players->podium(3)),
            'wanted' => array_map(fn (object $player) => $this->withPlace($player, (int) $player->logged === 1), $this->players->wanted(5)),
        ];
    }

    /** Adds ->place (sub-area name + coordinates) when the location may be shown. */
    private function withPlace(object $player, bool $visible): object
    {
        $player->place = null;
        if ($visible && ($position = $this->game->mapPosition((int) $player->map)) !== null) {
            $name = $this->game->subAreaName($position->subArea);
            if ($name !== null) {
                $player->place = (object) ['name' => $name, 'x' => $position->x, 'y' => $position->y];
            }
        }
        return $player;
    }
}
