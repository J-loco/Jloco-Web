<?php

declare(strict_types=1);

// Test bootstrap: application autoloader (config/autoload.php) + the tests namespace.
require dirname(__DIR__) . '/config/autoload.php';

$vendorDir = getenv('JLOCO_VENDOR_DIR') ?: dirname(__DIR__) . '/vendor';
(require $vendorDir . '/autoload.php')->addPsr4('JLoco\\Web\\Tests\\', __DIR__ . '/');
