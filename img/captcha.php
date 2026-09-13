<?php
// Registration captcha image (src/Captcha.php). No database connection needed.
require_once __DIR__ . '/../configuration/configuration.php';
require_once __DIR__ . '/../include/helpers.php';
require_once __DIR__ . '/../include/session.php';

header('Content-Type: image/png');
header('Cache-Control: no-store');
echo StarLoco\Web\Captcha::generate();
