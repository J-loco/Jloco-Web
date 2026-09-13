<?php

declare(strict_types=1);

namespace StarLoco\Web\Http;

use StarLoco\Web\Support\Text;

/**
 * Immutable view of the current HTTP request.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $server
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $cookies,
        public readonly array $server,
    ) {
    }

    /** Builds the request from PHP globals; $basePath ("/dofus") is stripped from the path. */
    public static function fromGlobals(string $basePath): self
    {
        $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . ltrim($path, '/');
        if ($path === '/index.php') {
            $path = '/';
        }
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path, $_GET, $_POST, $_COOKIE, $_SERVER);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /** A string query parameter (invalid UTF-8 scrubbed), or $default when missing or not a string. */
    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? null;
        return is_string($value) ? Text::scrub($value) : $default;
    }

    /** A string POST field (invalid UTF-8 scrubbed), or $default when missing or not a string. */
    public function input(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? null;
        return is_string($value) ? Text::scrub($value) : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post);
    }

    public function cookie(string $key): ?string
    {
        $value = $this->cookies[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    public function clientIp(bool $trustCloudflare): string
    {
        if ($trustCloudflare && !empty($this->server['HTTP_CF_CONNECTING_IP'])) {
            return (string) $this->server['HTTP_CF_CONNECTING_IP'];
        }
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
