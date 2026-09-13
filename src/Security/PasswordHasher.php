<?php

declare(strict_types=1);

namespace StarLoco\Web\Security;

use StarLoco\Web\Config;

/**
 * Account password hashes, shared with StarLoco-Login (org.starloco.locos.login.packet.Password).
 *
 * Two formats live side by side in world_accounts.pass:
 *  - legacy: hex SHA-512 of the hex MD5 of the password (unsalted, fast: weak);
 *  - pbkdf2: "pbkdf2_sha512$<iterations>$<base64 salt>$<base64 64-byte key>".
 *
 * Both are always accepted. New hashes use PASSWORD_HASH_SCHEME: keep "legacy" until the login
 * server that understands pbkdf2 is deployed, then switch to "pbkdf2"; legacy hashes are upgraded
 * at the next successful login (site or game).
 */
final readonly class PasswordHasher
{
    public const string SCHEME_LEGACY = 'legacy';
    public const string SCHEME_PBKDF2 = 'pbkdf2';

    /** OWASP 2023 recommendation for PBKDF2-HMAC-SHA512. Must match StarLoco-Login. */
    public const int PBKDF2_ITERATIONS = 210_000;
    private const string PBKDF2_PREFIX = 'pbkdf2_sha512';
    private const int SALT_BYTES = 16;
    private const int KEY_BYTES = 64;

    public function __construct(private Config $config)
    {
    }

    public function hash(string $password): string
    {
        return $this->config->passwordHashScheme === self::SCHEME_PBKDF2 ? self::pbkdf2($password) : self::legacy($password);
    }

    public function verify(string $password, string $hash): bool
    {
        if (str_starts_with($hash, self::PBKDF2_PREFIX . '$')) {
            $parts = explode('$', $hash);
            if (count($parts) !== 4 || !ctype_digit($parts[1]) || (int) $parts[1] < 1) {
                return false;
            }
            $salt = base64_decode($parts[2], true);
            $expected = base64_decode($parts[3], true);
            if ($salt === false || $expected === false) {
                return false;
            }
            return hash_equals($expected, hash_pbkdf2('sha512', $password, $salt, (int) $parts[1], strlen($expected), true));
        }
        return hash_equals($hash, self::legacy($password));
    }

    /** Whether a hash should be replaced by one in the configured scheme (after a successful verify). */
    public function needsRehash(string $hash): bool
    {
        if ($this->config->passwordHashScheme !== self::SCHEME_PBKDF2) {
            return false;
        }
        return !str_starts_with($hash, self::PBKDF2_PREFIX . '$' . self::PBKDF2_ITERATIONS . '$');
    }

    public static function legacy(string $password): string
    {
        return hash('sha512', md5($password));
    }

    public static function pbkdf2(string $password, ?string $salt = null, int $iterations = self::PBKDF2_ITERATIONS): string
    {
        $salt ??= random_bytes(self::SALT_BYTES);
        $key = hash_pbkdf2('sha512', $password, $salt, $iterations, self::KEY_BYTES, true);
        return self::PBKDF2_PREFIX . '$' . $iterations . '$' . base64_encode($salt) . '$' . base64_encode($key);
    }
}
