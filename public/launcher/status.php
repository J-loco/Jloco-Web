<?php

declare(strict_types=1);

/**
 * Server status for the JLoco launcher (JLoco-Client/launcher). URL contract, do not move.
 * GET -> {"name":string,"login":bool,"game":bool,"players":int|null}
 */

use JLoco\Web\Config;
use JLoco\Web\Repository\ServerRepository;
use JLoco\Web\Service\ServerStatus;

$container = require dirname(__DIR__, 2) . '/config/container.php';
$status = $container->get(ServerStatus::class);

try {
    $players = $container->get(ServerRepository::class)->countOnlineAccounts();
} catch (Throwable $e) {
    error_log('JLoco-Web launcher/status: ' . $e->getMessage());
    $players = null;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
echo json_encode([
    'name' => $container->get(Config::class)->siteName,
    'login' => $status->loginOnline(),
    'game' => $status->gameOnline(),
    'players' => $players,
], JSON_UNESCAPED_UNICODE);
