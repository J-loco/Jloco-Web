<?php

declare(strict_types=1);

namespace StarLoco\Web\Tests\Unit\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use StarLoco\Web\Config;
use StarLoco\Web\Controller\DropController;
use StarLoco\Web\Migration\Migrator;
use StarLoco\Web\Model\Account;
use StarLoco\Web\Model\Drop;
use StarLoco\Web\Model\MapPosition;
use StarLoco\Web\Service\AuthService;
use StarLoco\Web\Service\ForumFeed;
use StarLoco\Web\Service\JobLadder;
use StarLoco\Web\Service\ServerStatus;
use StarLoco\Web\View\AppExtension;

/** Pure logic of services, models and helpers (no database). */
final class DomainLogicTest extends TestCase
{
    public function testJobRankingSortsByLevelThenExperience(): void
    {
        $ranking = JobLadder::rank(
            [
                'Alice' => [24 => 50, 26 => 10],     // level 2
                'Bob' => [24 => 140],                // level 3
                'Carol' => [24 => 60],               // level 2, more xp than Alice
                'Dave' => [26 => 999_999],           // no job 24
            ],
            24,
            [24 => 'Mineur', 26 => 'Alchimiste'],
            10,
        );

        self::assertSame(['Bob', 'Carol', 'Alice'], array_map(static fn (\StarLoco\Web\Model\JobRanking $r): string => $r->name, $ranking));
        self::assertSame(3, $ranking[0]->level);
        self::assertSame(['Alchimiste'], $ranking[2]->otherJobs);
        self::assertCount(1, JobLadder::rank(['Alice' => [24 => 1], 'Bob' => [24 => 2]], 24, [], 1), 'limit');
    }

    public function testDropsAreGroupedByMonsterOrItem(): void
    {
        $drops = [
            new Drop(1, 'Bouftou', 10, 'Laine', 0, 60.0, 80.0),
            new Drop(1, 'Bouftou', 11, 'Cuir', 100, 40.0, 60.0),
            new Drop(2, 'Boufton', 10, 'Laine', 0, 20.0, 30.0),
        ];

        $byMonster = DropController::group($drops, 'monster');
        self::assertSame(['Bouftou', 'Boufton'], array_map(static fn (\StarLoco\Web\Model\DropGroup $g): string => $g->name, $byMonster));
        self::assertSame(['Laine', 'Cuir'], array_map(static fn (\StarLoco\Web\Model\DropLine $l): string => $l->name, $byMonster[0]->lines));

        $byItem = DropController::group($drops, 'item');
        self::assertSame(['Laine', 'Cuir'], array_map(static fn (\StarLoco\Web\Model\DropGroup $g): string => $g->name, $byItem));
        self::assertSame(['Bouftou', 'Boufton'], array_map(static fn (\StarLoco\Web\Model\DropLine $l): string => $l->name, $byItem[0]->lines));
    }

    public function testPasswordAndAccountNameRulesMatchTheLoginServer(): void
    {
        self::assertMatchesRegularExpression(AuthService::ACCOUNT_NAME_PATTERN, 'jean.pierre@mail-1');
        self::assertDoesNotMatchRegularExpression(AuthService::ACCOUNT_NAME_PATTERN, 'jean_pierre', 'the login server rejects "_"');
        self::assertDoesNotMatchRegularExpression(AuthService::ACCOUNT_NAME_PATTERN, 'ab');

        self::assertNull(AuthService::passwordError('Secret 123!', 'Secret 123!'));
        self::assertNotNull(AuthService::passwordError('Secrèt123', 'Secrèt123'), 'non-ASCII');
        self::assertNotNull(AuthService::passwordError('12345', '12345'), 'too short');
        self::assertSame('Les mots de passe ne sont pas identiques.', AuthService::passwordError('Secret123', 'Secret124'));
    }

    public function testAccountLegacyDates(): void
    {
        self::assertEquals(new DateTimeImmutable('2026-09-13 18:41:00'), Account::parseLegacyDate('2026~09~13~18~41'));
        self::assertNull(Account::parseLegacyDate(null));
        self::assertNull(Account::parseLegacyDate('garbage'));
    }

    public function testMapPositionParsing(): void
    {
        self::assertEquals(new MapPosition(-9, -12, 537), MapPosition::fromMappos('-9,-12,537'));
        self::assertEquals(new MapPosition(0, 0, 0), MapPosition::fromMappos(''));
    }

    public function testShopServersSetting(): void
    {
        self::assertSame([1 => 'starloco_game', 2 => 'game_2'], Config::parseShopServers('1:starloco_game, 2:game_2'));
        self::assertSame([], Config::parseShopServers('x:game,3:bad-name;,'));
    }

    public function testUptimeAndDurationFormatting(): void
    {
        self::assertSame('1j 2h 3m', ServerStatus::formatUptime(0, ((86400 + 7200 + 180) * 1000) + 999));
        self::assertSame('3 h', AppExtension::duration(3 * 3600));
        self::assertSame('2 h 1 min', AppExtension::duration(2 * 3600 + 1));
        self::assertSame('0 min', AppExtension::duration(0));
        self::assertSame('13 septembre 2026', AppExtension::formatDate(new DateTimeImmutable('2026-09-13')));
    }

    public function testForumFeedKeepsOnlyTextAndHttpLinks(): void
    {
        $xml = '<rss><channel>'
            . '<item><title>Mise &amp; jour</title><link>javascript:alert(1)</link><description>&lt;b&gt;Hello&lt;/b&gt; &lt;script&gt;x&lt;/script&gt;</description><pubDate>Sun, 13 Sep 2026 10:00:00 +0000</pubDate></item>'
            . '<item><title>Deux</title><link>https://forum.example/2</link><description>x</description></item>'
            . '</channel></rss>';

        $topics = ForumFeed::parse($xml);
        self::assertCount(2, $topics);
        self::assertSame('Mise & jour', $topics[0]->title);
        self::assertNull($topics[0]->link);
        self::assertSame('Hello x', $topics[0]->summary);
        self::assertNotNull($topics[0]->publishedAt);
        self::assertSame('https://forum.example/2', $topics[1]->link);
        self::assertSame([], ForumFeed::parse('not xml'));
    }

    public function testMigrationFilesAreSplitIntoStatements(): void
    {
        self::assertSame(
            ['CREATE TABLE a (id INT)', 'INSERT INTO a VALUES (1);2'],
            Migrator::statements("-- comment\nCREATE TABLE a (id INT);\n  -- another\nINSERT INTO a VALUES (1);2;\n"),
        );
    }
}
