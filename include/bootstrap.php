<?php
/**
 * Shared setup for portal requests: configuration, security headers, session, helpers.
 */

require_once __DIR__ . '/../configuration/configuration.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../class/Experience.class.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$sessionCookie = cookie_options();
unset($sessionCookie['expires']);
session_set_cookie_params(['lifetime' => 0] + $sessionCookie);
session_start();
