<?php
/**
 * Composer autoloader + StarLoco\Web\ classes + optional StarLoco-Web/.env.
 *
 * In the Docker image, dependencies live outside the bind-mounted code (STARLOCO_VENDOR_DIR),
 * so the application classes are registered here rather than through the vendor's own map.
 */
(static function (): void {
	$root = dirname(__DIR__);
	$vendorDir = getenv('STARLOCO_VENDOR_DIR') ?: $root . '/vendor';

	if (!is_file($vendorDir . '/autoload.php')) {
		http_response_code(503);
		exit('StarLoco-Web: dependencies missing, run "composer install" (or rebuild the Docker image).');
	}

	$loader = require $vendorDir . '/autoload.php';
	$loader -> addPsr4('StarLoco\\Web\\', $root . '/src/');

	Dotenv\Dotenv::createImmutable($root) -> safeLoad();
})();
