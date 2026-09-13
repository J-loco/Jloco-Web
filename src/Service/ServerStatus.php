<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use StarLoco\Web\Config;
use StarLoco\Web\Repository\PlayerRepository;
use StarLoco\Web\Repository\ServerRepository;

/**
 * Login/game server availability (TCP probe) and population, for the sidebar and the launcher.
 */
final class ServerStatus
{
    /** @var array<string, bool> */
    private array $probes = [];

    public function __construct(
        private readonly Config $config,
        private readonly ServerRepository $servers,
        private readonly PlayerRepository $players,
    ) {
    }

    public function loginOnline(): bool
    {
        return $this->probe($this->config->loginServerHost, $this->config->loginServerPort);
    }

    public function gameOnline(): bool
    {
        return $this->probe($this->config->gameServerHost, $this->config->gameServerPort);
    }

    /** @return object{name: string, online: bool, players: int, uptime: ?string} */
    public function gameServer(): object
    {
        $server = $this->servers->find($this->config->gameServerId);
        $online = $this->gameOnline();
        return (object) [
            'name' => $server->name ?? $this->config->siteName,
            'online' => $online,
            'players' => $this->players->countOnline($this->config->gameServerId),
            'uptime' => $online && $server !== null && (int) $server->uptime > 0 ? self::formatUptime((int) $server->uptime) : null,
        ];
    }

    /** world_servers.uptime is the start time in milliseconds. */
    public static function formatUptime(int $startedAtMs): string
    {
        $seconds = max(0, intdiv((int) round(microtime(true) * 1000) - $startedAtMs, 1000));
        return sprintf('%dj %dh %dm', intdiv($seconds, 86400), intdiv($seconds % 86400, 3600), intdiv($seconds % 3600, 60));
    }

    private function probe(string $host, int $port): bool
    {
        $key = $host . ':' . $port;
        if (!isset($this->probes[$key])) {
            $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 1);
            $this->probes[$key] = $socket !== false;
            if ($socket !== false) {
                fclose($socket);
            }
        }
        return $this->probes[$key];
    }
}
