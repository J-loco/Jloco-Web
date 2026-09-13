<?php

declare(strict_types=1);

/**
 * Service wiring. Everything else is autowired by constructor type (src/Container.php).
 */

use StarLoco\Web\Config;
use StarLoco\Web\Container;
use StarLoco\Web\Http\Request;
use StarLoco\Web\View\TwigFactory;
use Twig\Environment;

require_once __DIR__ . '/autoload.php';

$container = new Container();

$container->factory(Config::class, static fn () => Config::fromEnvironment());
$container->factory(Request::class, static fn (Container $c) => Request::fromGlobals($c->get(Config::class)->basePath()));
$container->factory(Environment::class, static fn (Container $c) => TwigFactory::create($c));

$config = $container->get(Config::class);
ini_set('display_errors', $config->debug ? '1' : '0');
date_default_timezone_set('Europe/Paris');

return $container;
