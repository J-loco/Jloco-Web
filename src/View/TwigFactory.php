<?php

declare(strict_types=1);

namespace StarLoco\Web\View;

use StarLoco\Web\Config;
use StarLoco\Web\Container;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigFactory
{
    public static function create(Container $container): Environment
    {
        $config = $container->get(Config::class);

        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2) . '/templates'), [
            // Per system user: CLI tools (often root) and Apache (www-data) must not share a cache directory.
            'cache' => sys_get_temp_dir() . '/starloco-web-twig-' . (function_exists('posix_geteuid') ? posix_geteuid() : get_current_user()),
            'auto_reload' => true,
            'strict_variables' => $config->debug,
            'autoescape' => 'html',
        ]);
        $twig->addExtension($container->get(AppExtension::class));
        $twig->addGlobal('config', $config);

        return $twig;
    }
}
