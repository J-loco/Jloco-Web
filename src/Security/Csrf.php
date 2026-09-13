<?php

declare(strict_types=1);

namespace StarLoco\Web\Security;

/**
 * One token per session, sent as the "_csrf" field of every POST form.
 */
final class Csrf
{
    public const FIELD = '_csrf';
    private const SESSION_KEY = '_csrf';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public function isValid(?string $token): bool
    {
        $expected = $this->session->get(self::SESSION_KEY);
        return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
    }
}
