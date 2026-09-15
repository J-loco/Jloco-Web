<?php

declare(strict_types=1);

/**
 * Composer autoloader + JLoco\Web\ classes + optional JLoco-Web/.env.
 *
 * In the Docker image, dependencies live outside the bind-mounted code (JLOCO_VENDOR_DIR),
 * so the application classes are registered here rather than through the vendor's own map.
 */
(static function (): void {
    $root = dirname(__DIR__);
    $vendorDir = getenv('JLOCO_VENDOR_DIR') ?: $root . '/vendor';

    if (!is_file($vendorDir . '/autoload.php')) {
        http_response_code(503);
        exit('JLoco-Web: dependencies missing, run "composer install" (or rebuild the Docker image).');
    }

    $loader = require $vendorDir . '/autoload.php';
    $loader->addPsr4('JLoco\\Web\\', $root . '/src/');

    Dotenv\Dotenv::createImmutable($root)->safeLoad();
})();
