<?php

declare(strict_types=1);

namespace JLoco\Web\Tests\Unit\Game;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use JLoco\Web\Game\Experience;

final class ExperienceTest extends TestCase
{
    public function testTablesMirrorTheLuaMaximumLevels(): void
    {
        self::assertCount(Experience::MAX_PLAYER_LEVEL, Experience::PLAYER);
        self::assertCount(Experience::MAX_JOB_LEVEL, Experience::JOB);
        self::assertSame(0, Experience::PLAYER[0]);
        self::assertSame(110, Experience::PLAYER[1]);
    }

    /** @return iterable<string, array{int, int}> */
    public static function jobLevels(): iterable
    {
        yield 'no experience' => [0, 1];
        yield 'just below level 2' => [49, 1];
        yield 'exactly level 2' => [50, 2];
        yield 'level 3' => [140, 3];
        yield 'far above the table is capped' => [10_000_000, Experience::MAX_JOB_LEVEL];
    }

    #[DataProvider('jobLevels')]
    public function testLevelFromXp(int $xp, int $expectedLevel): void
    {
        self::assertSame($expectedLevel, Experience::levelFromXp(Experience::JOB, Experience::MAX_JOB_LEVEL, $xp));
    }

    public function testProgressWithinALevel(): void
    {
        // Level 1 -> 2 for players: 0 -> 110 XP.
        self::assertSame(0, Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, 1, 0));
        self::assertSame(50, Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, 1, 55));
        self::assertSame(99, Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, 1, 109));
    }

    public function testProgressIsClampedAndFullAtMaxLevel(): void
    {
        self::assertSame(0, Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, 10, 0));
        self::assertSame(100, Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, 200, 0));
        self::assertSame(11, Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, 199, 4_138_883_040));
    }

    public function testFormat(): void
    {
        self::assertSame('05%', Experience::format(5));
        self::assertSame('100%', Experience::format(100));
    }
}
