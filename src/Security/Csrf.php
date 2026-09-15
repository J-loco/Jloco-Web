<?php

declare(strict_types=1);

namespace JLoco\Web\Security;

/**
 * One token per session, sent as the "_csrf" field of every POST form.
 */
final readonly class Csrf
{
    public const string FIELD = '_csrf';
    private const string SESSION_KEY = '_csrf';

    public function __construct(private Session $session)
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
