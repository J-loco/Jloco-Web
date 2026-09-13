<?php

declare(strict_types=1);

namespace StarLoco\Web\Tests\Unit\Game;

use PHPUnit\Framework\TestCase;
use StarLoco\Web\Game\Character;
use StarLoco\Web\Game\ItemEffects;

final class ItemEffectsAndCharacterTest extends TestCase
{
    public function testDescribesRangesAndFixedValues(): void
    {
        self::assertSame(
            ['+ 10 à 20 Vitalité', '+ 1 Pa'],
            ItemEffects::describe('7d#a#14#0#1d10+9,6f#1#0#0#0d0+1'),
        );
    }

    public function testNegativeEffectsKeepTheirSign(): void
    {
        self::assertSame(['- 5 Terre'], ItemEffects::describe('9d#5#0#0#0d0+5'));
    }

    public function testUnknownAndMalformedEffectsAreSkipped(): void
    {
        self::assertSame([], ItemEffects::describe(''));
        self::assertSame([], ItemEffects::describe('zz#1#2#0#0d0+0'));
        self::assertSame([], ItemEffects::describe('7d'));
    }

    public function testBreedLabelsDependOnSex(): void
    {
        self::assertSame('Iop', Character::breed(8, 0));
        self::assertSame('Iopette', Character::breed(8, 1));
        self::assertSame('Inconnu', Character::breed(99, 0));
    }

    public function testAlignmentLabels(): void
    {
        self::assertSame('Bonta', Character::alignment(1));
        self::assertSame('Neutre', Character::alignment(42));
    }
}
