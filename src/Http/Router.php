<?php

declare(strict_types=1);

namespace JLoco\Web\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use InvalidArgumentException;
use JLoco\Web\Config;

use function FastRoute\simpleDispatcher;

/**
 * Named routes (config/routes.php) on top of FastRoute, with URL generation.
 */
final class Router
{
    /** @var array<string, Route> */
    private array $routes = [];

    private ?Dispatcher $dispatcher = null;

    public function __construct(private readonly Config $config)
    {
        foreach (require dirname(__DIR__, 2) . '/config/routes.php' as $route) {
            $this->routes[$route->name] = $route;
        }
    }

    /**
     * @return array{0: int, 1?: Route, 2?: array<string, string>}
     *         [Dispatcher::FOUND, route, params] | [Dispatcher::NOT_FOUND] | [Dispatcher::METHOD_NOT_ALLOWED]
     */
    public function match(string $method, string $path): array
    {
        $this->dispatcher ??= simpleDispatcher(function (RouteCollector $collector): void {
            foreach ($this->routes as $route) {
                $collector->addRoute($route->methods, $route->pattern, $route->name);
            }
        });

        // HEAD is answered like GET.
        $result = $this->dispatcher->dispatch($method === 'HEAD' ? 'GET' : $method, $path);
        if ($result[0] !== Dispatcher::FOUND) {
            return [$result[0]];
        }
        return [Dispatcher::FOUND, $this->routes[$result[1]], $result[2]];
    }

    /**
     * Absolute URL of a named route. Pattern placeholders are filled from $params; the remaining
     * params become the query string.
     *
     * @param array<string, scalar|null> $params null and '' query values are left out
     */
    public function url(string $name, array $params = []): string
    {
        $route = $this->routes[$name] ?? throw new InvalidArgumentException("Unknown route \"$name\"");

        // FastRoute's own parser: placeholder regexes may contain braces ("{token:[a-f0-9]{64}}").
        $variants = new RouteParser\Std()->parse($route->pattern);
        $path = '';
        foreach (end($variants) as $part) {
            if (is_string($part)) {
                $path .= $part;
                continue;
            }
            [$placeholder] = $part;
            if (!array_key_exists($placeholder, $params)) {
                throw new InvalidArgumentException("Route \"$name\" needs parameter \"$placeholder\"");
            }
            $path .= rawurlencode((string) $params[$placeholder]);
            unset($params[$placeholder]);
        }

        $query = http_build_query(array_filter($params, static fn (int|float|string|bool|null $value): bool => $value !== null && $value !== ''));
        return rtrim($this->config->appUrl, '/') . $path . ($query !== '' ? '?' . $query : '');
    }
}
