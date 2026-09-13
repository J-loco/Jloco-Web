<?php
// Buffer the page so any include can still redirect (send headers) after output started.
ob_start();

require_once __DIR__ . '/include/bootstrap.php';

$pages = require __DIR__ . '/include/routes.php';
$pageName = isset($_GET['page']) && is_string($_GET['page']) && $_GET['page'] !== '' ? $_GET['page'] : 'index';

// CSRF: every POST must carry the session token. The Dedipass widget builds its own form,
// so it cannot; its codes are verified server-side against the Dedipass API instead.
$isDedipassCallback = $pageName === 'profile' && isset($_POST['code'], $_POST['rate']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isDedipassCallback && !csrf_valid($_POST['_csrf'] ?? null)) {
	flash('warning', 'Ta session a expiré, merci de réessayer.');
	redirect($_SERVER['REQUEST_URI'] ?? url());
}

// Login (sign-in page and the modal in the footer both post here).
if (isset($_POST['login'])) {
	if (throttle_blocked($login, 'login')) {
		flash('danger', throttle_message());
		redirect(url('signin'));
	}

	$account = auth_check_credentials($login, (string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));
	if ($account === null) {
		throttle_fail($login, 'login');
		flash('danger', 'Tes identifiants sont incorrects !');
		redirect(url('signin'));
	}

	throttle_clear($login, 'login');
	auth_login($account);
	if (isset($_POST['remember']))
		remember_issue($login, $account -> guid);

	flash('success', 'La connexion a été effectuée avec succès !');
	redirect(url());
}

if ($pageName === 'logout') {
	if (csrf_valid($_GET['token'] ?? null)) {
		auth_logout($login);
		flash('info', 'Tu t\'es déconnecté avec succès !');
	}
	redirect(url());
}

if (!is_logged_in() && ($account = remember_restore($login)) !== null)
	auth_login($account);

if (!array_key_exists($pageName, $pages)) {
	http_response_code(404);
	include __DIR__ . '/pages/404.php';
	echo "<meta http-equiv='refresh' content='3; url=" . e(url()) . "'>";
	return;
}

include __DIR__ . '/include/header.php';
include __DIR__ . '/pages/' . $pageName . '.php';
if ($pages[$pageName])
	include __DIR__ . '/include/rightmenu.php';
include __DIR__ . '/include/footer.php';
