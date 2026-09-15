<?php

declare(strict_types=1);

namespace JLoco\Web\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use JLoco\Web\Support\Text;

final class TextTest extends TestCase
{
    public function testLatin1Characters(): void
    {
        self::assertTrue(Text::fitsLatin1('Épée de Bouftou à 100 %'));
        self::assertTrue(Text::fitsLatin1(''));
        self::assertFalse(Text::fitsLatin1('日本'));
        self::assertFalse(Text::fitsLatin1('Bouftou 😀'));
        self::assertFalse(Text::fitsLatin1('œuvre'), 'œ is not in ISO-8859-1');
        self::assertFalse(Text::fitsLatin1("caf\xE9"), 'invalid UTF-8');
    }

    public function testScrubReplacesInvalidBytesOnly(): void
    {
        self::assertSame('Épée', Text::scrub('Épée'));
        self::assertSame('caf?', Text::scrub("caf\xE9"));
    }

    public function testRemovesCharactersOutsideTheBasicPlane(): void
    {
        self::assertSame('Bouftou royal', Text::withoutSupplementaryCharacters('Bouftou 👑royal'));
        self::assertSame('Épée', Text::withoutSupplementaryCharacters('Épée'));
    }
}
