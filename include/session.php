<?php
/**
 * Security headers + hardened session. Needs configuration.php and helpers.php.
 */
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
$sessionCookie = cookie_options();
unset($sessionCookie['expires']);
session_set_cookie_params(['lifetime' => 0] + $sessionCookie);
session_start();
unset($sessionCookie);
