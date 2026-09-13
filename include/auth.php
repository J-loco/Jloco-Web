<?php
/**
 * Authentication: login/logout, remember-me tokens and brute-force throttling.
 * Tables: website_remember_tokens, website_auth_attempts (StarLoco-Game/db-init/12-update_login_web_security.sql).
 */

const THROTTLE_MAX_FAILURES = 5;
const THROTTLE_WINDOW_MINUTES = 15;
const REMEMBER_COOKIE = 'remember';
const REMEMBER_DAYS = 7;

/** Password format shared with StarLoco-Login; changing it requires changing the login server too. */
function legacy_password_hash(string $password): string {
	return hash('sha512', md5($password));
}

/** Returns the account (guid, account) when the credentials match, null otherwise. */
function auth_check_credentials(PDO $db, string $username, string $password): ?object {
	$query = $db -> prepare('SELECT guid, account, pass FROM world_accounts WHERE account = ?;');
	$query -> execute([$username]);
	$row = $query -> fetch(PDO::FETCH_OBJ);
	$query -> closeCursor();

	if (!$row || !hash_equals((string) $row -> pass, legacy_password_hash($password)))
		return null;
	return (object) ['guid' => (int) $row -> guid, 'account' => $row -> account];
}

function auth_login(object $account): void {
	session_regenerate_id(true);
	$_SESSION['user'] = $account -> account;
	$_SESSION['id'] = (int) $account -> guid;
	$_SESSION['data'] = $account;
}

function auth_logout(PDO $db): void {
	remember_forget($db);
	$_SESSION = [];
	session_regenerate_id(true);
}

/* ---------------------------------------------------------------- remember me */

function remember_issue(PDO $db, int $accountId): void {
	$selector = bin2hex(random_bytes(12));
	$validator = bin2hex(random_bytes(32));
	$expires = time() + REMEMBER_DAYS * 86400;

	$query = $db -> prepare('INSERT INTO website_remember_tokens (selector, validator_hash, account, expires_at) VALUES (?, ?, ?, FROM_UNIXTIME(?));');
	$query -> execute([$selector, hash('sha256', $validator), $accountId, $expires]);

	setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, cookie_options($expires));
}

/** Logs the visitor back in from a valid remember-me cookie, rotating the token. */
function remember_restore(PDO $db): ?object {
	$cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
	if (!is_string($cookie) || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $parts))
		return null;
	[, $selector, $validator] = $parts;

	$query = $db -> prepare('SELECT t.validator_hash, a.guid, a.account FROM website_remember_tokens t JOIN world_accounts a ON a.guid = t.account WHERE t.selector = ? AND t.expires_at > NOW();');
	$query -> execute([$selector]);
	$row = $query -> fetch(PDO::FETCH_OBJ);
	$query -> closeCursor();

	// Single use: the token is deleted whether it matches or not.
	$db -> prepare('DELETE FROM website_remember_tokens WHERE selector = ?;') -> execute([$selector]);

	if (!$row || !hash_equals($row -> validator_hash, hash('sha256', $validator))) {
		setcookie(REMEMBER_COOKIE, '', cookie_options(1));
		return null;
	}

	remember_issue($db, (int) $row -> guid);
	return (object) ['guid' => (int) $row -> guid, 'account' => $row -> account];
}

function remember_forget(PDO $db): void {
	$cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
	if (is_string($cookie) && str_contains($cookie, ':'))
		$db -> prepare('DELETE FROM website_remember_tokens WHERE selector = ?;') -> execute([explode(':', $cookie)[0]]);
	setcookie(REMEMBER_COOKIE, '', cookie_options(1));
}

/* ---------------------------------------------------------------- throttling */

function throttle_blocked(PDO $db, string $action): bool {
	$query = $db -> prepare('SELECT COUNT(*) FROM website_auth_attempts WHERE ip = ? AND action = ? AND attempted_at > NOW() - INTERVAL ' . THROTTLE_WINDOW_MINUTES . ' MINUTE;');
	$query -> execute([client_ip(), $action]);
	return (int) $query -> fetchColumn() >= THROTTLE_MAX_FAILURES;
}

function throttle_fail(PDO $db, string $action): void {
	$db -> prepare('INSERT INTO website_auth_attempts (ip, action, attempted_at) VALUES (?, ?, NOW());') -> execute([client_ip(), $action]);
	if (random_int(1, 50) === 1)
		$db -> exec('DELETE FROM website_auth_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY;');
}

function throttle_clear(PDO $db, string $action): void {
	$db -> prepare('DELETE FROM website_auth_attempts WHERE ip = ? AND action = ?;') -> execute([client_ip(), $action]);
}

function throttle_message(): string {
	return 'Trop de tentatives. Réessaie dans ' . THROTTLE_WINDOW_MINUTES . ' minutes.';
}
