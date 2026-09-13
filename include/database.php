<?php
/**
 * Connections used by the legacy pages as globals: $login (= $connection) and $jiva.
 * Phase 2 replaces the globals with repositories receiving StarLoco\Web\Database.
 */
try {
	$login = database() -> login();
	$connection = $login;
	$jiva = database() -> game();
} catch (PDOException $e) {
	error_log('StarLoco-Web: database unavailable: ' . $e -> getMessage());
	http_response_code(503);
	exit(APP_DEBUG ? 'Error : ' . e($e -> getMessage()) : 'Service temporairement indisponible.');
}
