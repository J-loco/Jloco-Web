<?php
/**
 * Server status for the StarLoco launcher.
 * GET -> {"login":bool,"game":bool,"players":int|null,"name":string}
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../configuration/configuration.php');

$login = checkState(LOGIN_IP, LOGIN_PORT);
$game  = checkState(JIVA_IP, JIVA_PORT);

$players = null;
try {
    $query = $connection->query('SELECT COUNT(*) FROM `world_accounts` WHERE `logged` = 1;');
    $players = (int) $query->fetchColumn();
    $query->closeCursor();
} catch (Exception $e) {
    $players = null;
}

echo json_encode(array(
    'name'    => TITLE,
    'login'   => $login,
    'game'    => $game,
    'players' => $players
));
