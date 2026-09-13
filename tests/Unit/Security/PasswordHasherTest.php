<?php

declare(strict_types=1);

namespace StarLoco\Web\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use StarLoco\Web\Security\PasswordHasher;
use StarLoco\Web\Tests\Support\TestConfig;

/**
 * The vectors below are shared with StarLoco-Login (PasswordTest): site and game server must agree.
 */
final class PasswordHasherTest extends TestCase
{
    /** SHA-512 of MD5("Secret123"), as stored by the login server and by MariaDB SHA2(MD5(...), 512). */
    public const string LEGACY_VECTOR = 'feb8908e6856152712b5206ec3d0e2b0ef9bbd4c9d518fdfde5851ba5673125f45615b7ced2a6f177e37cbe9820c9f250192d48ccb4d01f5b3ea77a86fc09b5c';

    /** PBKDF2-HMAC-SHA512("Secret123", salt = bytes 0..15, 210000 iterations, 64 bytes). */
    public const string PBKDF2_VECTOR = 'pbkdf2_sha512$210000$AAECAwQFBgcICQoLDA0ODw==$iZtt5xyR1Etmq2tlOF9CV/QVTjl/9i+yhjOPuGSDI6QIyQ1QkpLGIX79y9cibM8TrXmsPxPWGmYpatx0UdBWfQ==';

    public function testLegacyFormatMatchesTheLoginServer(): void
    {
        self::assertSame(self::LEGACY_VECTOR, PasswordHasher::legacy('Secret123'));
    }

    public function testPbkdf2FormatMatchesTheReferenceVector(): void
    {
        self::assertSame(self::PBKDF2_VECTOR, PasswordHasher::pbkdf2('Secret123', implode('', array_map(chr(...), range(0, 15)))));
    }

    public function testVerifiesBothFormats(): void
    {
        $hasher = new PasswordHasher(TestConfig::make());

        self::assertTrue($hasher->verify('Secret123', self::LEGACY_VECTOR));
        self::assertTrue($hasher->verify('Secret123', self::PBKDF2_VECTOR));
        self::assertFalse($hasher->verify('secret123', self::LEGACY_VECTOR));
        self::assertFalse($hasher->verify('secret123', self::PBKDF2_VECTOR));
    }

    public function testRejectsMalformedPbkdf2Hashes(): void
    {
        $hasher = new PasswordHasher(TestConfig::make());

        self::assertFalse($hasher->verify('Secret123', 'pbkdf2_sha512$0$AAAA$AAAA'));
        self::assertFalse($hasher->verify('Secret123', 'pbkdf2_sha512$210000$not-base64!$AAAA'));
        self::assertFalse($hasher->verify('Secret123', 'pbkdf2_sha512$210000$AAAA'));
        self::assertFalse($hasher->verify('Secret123', ''));
    }

    public function testNewHashesFollowTheConfiguredScheme(): void
    {
        $legacy = new PasswordHasher(TestConfig::make(['passwordHashScheme' => PasswordHasher::SCHEME_LEGACY]));
        $pbkdf2 = new PasswordHasher(TestConfig::make(['passwordHashScheme' => PasswordHasher::SCHEME_PBKDF2]));

        self::assertSame(self::LEGACY_VECTOR, $legacy->hash('Secret123'));

        $hash = $pbkdf2->hash('Secret123');
        self::assertStringStartsWith('pbkdf2_sha512$210000$', $hash);
        self::assertNotSame($hash, $pbkdf2->hash('Secret123'), 'salt must be random');
        self::assertTrue($pbkdf2->verify('Secret123', $hash));
    }

    public function testRehashOnlyWhenMigratingToPbkdf2(): void
    {
        $legacy = new PasswordHasher(TestConfig::make(['passwordHashScheme' => PasswordHasher::SCHEME_LEGACY]));
        $pbkdf2 = new PasswordHasher(TestConfig::make(['passwordHashScheme' => PasswordHasher::SCHEME_PBKDF2]));

        self::assertFalse($legacy->needsRehash(self::LEGACY_VECTOR));
        self::assertTrue($pbkdf2->needsRehash(self::LEGACY_VECTOR));
        self::assertFalse($pbkdf2->needsRehash(self::PBKDF2_VECTOR));
        self::assertTrue($pbkdf2->needsRehash('pbkdf2_sha512$1000$AAAA$AAAA'), 'fewer iterations than today');
    }
}
