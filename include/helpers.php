<?php
/**
 * Small helpers shared by every page. Phase 2 of docs/refactor moves them into src/.
 */

/** Escapes a value for HTML text or attribute context. */
function e($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Builds an internal link. Always use this instead of writing "?page=" by hand:
 * Phase 2 switches it to real paths (/ladder) without touching the pages.
 */
function url(string $page = 'index', array $params = []): string {
	$query = $page === 'index' ? $params : ['page' => $page] + $params;
	return URL_SITE . ($query ? '?' . http_build_query($query) : '');
}

/** Redirects (303, so a POST is followed by a GET) and stops the request. */
function redirect(string $location): void {
	while (ob_get_level() > 0)
		ob_end_clean();
	header('Location: ' . $location, true, 303);
	exit;
}

function base_path(): string {
	return parse_url(URL_SITE, PHP_URL_PATH) ?: '/';
}

function is_https(): bool {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| (TRUST_CLOUDFLARE && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/** Cookie options shared by the session cookie and the remember-me cookie. */
function cookie_options(int $expires = 0): array {
	return [
		'expires' => $expires,
		'path' => base_path(),
		'secure' => is_https(),
		'httponly' => true,
		'samesite' => 'Lax',
	];
}

function client_ip(): string {
	if (TRUST_CLOUDFLARE && !empty($_SERVER['HTTP_CF_CONNECTING_IP']))
		return (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
	return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function is_logged_in(): bool {
	return isset($_SESSION['id']);
}

function is_admin(): bool {
	return is_logged_in() && (int) $_SESSION['id'] === ADMIN_GUID;
}

/** Sends visitors who are not logged in to the sign-in page. */
function require_login(): void {
	if (!is_logged_in())
		redirect(url('signin'));
}

/** Queues a message shown once, on the next rendered page (include/header.php). */
function flash(string $type, string $message): void {
	$_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array {
	$flashes = $_SESSION['flash'] ?? [];
	unset($_SESSION['flash']);
	return $flashes;
}

/** Bootstrap alert markup used across pages. $message is escaped. */
function alert(string $type, string $message, string $title = ''): string {
	return '<div class="alert alert-' . e($type) . ' no-border-radius" style="text-align: center!important;" role="alert">'
		. ($title !== '' ? '<strong>' . e($title) . '</strong> ' : '')
		. e($message) . '</div>';
}

/* ---------------------------------------------------------------- CSRF */

function csrf_token(): string {
	if (empty($_SESSION['csrf']))
		$_SESSION['csrf'] = bin2hex(random_bytes(32));
	return $_SESSION['csrf'];
}

function csrf_field(): string {
	return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid($token): bool {
	return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Hidden inputs a GET form needs to stay on $page (a GET form drops the query string of its action).
 * Phase 2 (real paths) makes this return an empty string.
 */
function route_fields(string $page): string {
	return $page === 'index' ? '' : '<input type="hidden" name="page" value="' . e($page) . '">';
}
