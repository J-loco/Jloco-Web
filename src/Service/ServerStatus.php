<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use StarLoco\Web\Config;
use StarLoco\Web\Model\GameServerStatus;
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

    public function gameServer(): GameServerStatus
    {
        $server = $this->servers->find($this->config->gameServerId);
        $online = $this->gameOnline();

        return new GameServerStatus(
            name: $server->name ?? $this->config->siteName,
            online: $online,
            players: $this->players->countOnline($this->config->gameServerId),
            uptime: $online && $server !== null && $server->startedAtMs > 0 ? self::formatUptime($server->startedAtMs) : null,
        );
    }

    /** Time since $startedAtMs (milliseconds), e.g. "2j 3h 15m". */
    public static function formatUptime(int $startedAtMs, ?int $nowMs = null): string
    {
        $nowMs ??= (int) round(microtime(true) * 1000);
        $seconds = max(0, intdiv($nowMs - $startedAtMs, 1000));
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
