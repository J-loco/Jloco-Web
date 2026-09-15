<?php

declare(strict_types=1);

/**
 * Service wiring. Everything else is autowired by constructor type (src/Container.php).
 */

use JLoco\Web\Config;
use JLoco\Web\Container;
use JLoco\Web\Http\Request;
use JLoco\Web\View\TwigFactory;
use Twig\Environment;

require_once __DIR__ . '/autoload.php';

$container = new Container();

$container->factory(Config::class, static fn (): Config => Config::fromEnvironment());
$container->factory(Request::class, static fn (Container $c): Request => Request::fromGlobals($c->get(Config::class)->basePath()));
$container->factory(Environment::class, static fn (Container $c): Environment => TwigFactory::create($c));

$config = $container->get(Config::class);
ini_set('display_errors', $config->debug ? '1' : '0');
date_default_timezone_set('Europe/Paris');

return $container;
