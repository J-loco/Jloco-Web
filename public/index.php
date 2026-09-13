<?php

declare(strict_types=1);

// Front controller: every URL that is not a real file in public/ ends up here.
$container = require dirname(__DIR__) . '/config/container.php';
new StarLoco\Web\Kernel($container)->run();
