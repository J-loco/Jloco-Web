<?php

declare(strict_types=1);

namespace JLoco\Web\Http;

final readonly class Route
{
    /**
     * @param list<string> $methods
     * @param array{0: class-string, 1: string} $handler controller class and method
     * @param bool $csrf whether POST requests must carry the CSRF token
     */
    public function __construct(
        public array $methods,
        public string $pattern,
        public string $name,
        public array $handler,
        public bool $csrf = true,
    ) {
    }

    /** @param array{0: class-string, 1: string} $handler */
    public static function get(string $pattern, string $name, array $handler): self
    {
        return new self(['GET'], $pattern, $name, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public static function post(string $pattern, string $name, array $handler, bool $csrf = true): self
    {
        return new self(['POST'], $pattern, $name, $handler, $csrf);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public static function form(string $pattern, string $name, array $handler): self
    {
        return new self(['GET', 'POST'], $pattern, $name, $handler);
    }
}
