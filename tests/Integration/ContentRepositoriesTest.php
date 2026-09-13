<?php

declare(strict_types=1);

namespace StarLoco\Web\Tests\Integration;

use StarLoco\Web\Repository\GameRepository;
use StarLoco\Web\Repository\NewsRepository;
use StarLoco\Web\Repository\PlayerRepository;
use StarLoco\Web\Service\Sidebar;

/** News, characters, drops and the sidebar against the real schemas. */
final class ContentRepositoriesTest extends IntegrationTestCase
{
    public function testWebsiteAndGameNews(): void
    {
        $news = new NewsRepository($this->database);

        $first = $news->create('Équipe', 'Mise à jour', '<p>Contenu</p>');
        $news->create('Équipe', 'Deuxième', 'x');
        self::assertSame(2, $news->count());
        self::assertSame(['Deuxième', 'Mise à jour'], array_map(static fn (\StarLoco\Web\Model\NewsPost $p): string => $p->title, $news->latest(10)));
        self::assertSame('Mise à jour', $news->latest(1, 1)[0]->title);

        $news->delete($first);
        self::assertSame(1, $news->count());

        $news->createGameNews('Maintenance ce soir', 'Maintenance');
        $news->createGameNews('Event', 'Event');
        $game = $news->gameNews();
        self::assertSame([2, 1], array_map(static fn (\StarLoco\Web\Model\GameNews $n): int => $n->id, $game), 'ids continue from MAX(id): the column is not AUTO_INCREMENT');
        self::assertSame((int) date('Ymd'), $game[0]->date);
        $news->deleteGameNews(2);
        self::assertCount(1, $news->gameNews());
    }

    public function testRankingsOnlyIncludeNormalPlayers(): void
    {
        $account = $this->createAccount('alice');
        $base = ['account' => $account, 'color1' => 0, 'color2' => 0, 'color3' => 0, 'kamas' => 0, 'spellboost' => 0, 'capital' => 0, 'size' => 100, 'gfx' => 80, 'map' => 7411, 'cell' => 1, 'spells' => '', 'objets' => '', 'storeObjets' => '', 'server' => 601, 'sexe' => 1, 'class' => 8];
        $this->insert($this->login(), 'world_players', $base + ['name' => 'Staff', 'groupe' => 1, 'level' => 200, 'xp' => 9_000_000_000, 'jobs' => '']);
        $this->insert($this->login(), 'world_players', $base + ['name' => 'Iopette', 'groupe' => -1, 'level' => 50, 'xp' => 5_350_000, 'honor' => 10, 'jobs' => '24,140;2,50']);
        $this->insert($this->login(), 'world_players', $base + ['name' => 'Cra', 'groupe' => -1, 'level' => 10, 'xp' => 19_200, 'honor' => 99, 'jobs' => '2,999', 'deshonor' => 2, 'logged' => 1]);

        $players = new PlayerRepository($this->database);
        self::assertSame(['Iopette', 'Cra'], array_map(static fn (\StarLoco\Web\Model\Character $c): string => $c->name, $players->topByXp(10)));
        self::assertSame(['Cra', 'Iopette'], array_map(static fn (\StarLoco\Web\Model\Character $c): string => $c->name, $players->topByHonor(10)));
        self::assertSame(3, count($players->byAccount($account)));
        self::assertSame(['Cra'], array_map(static fn (\StarLoco\Web\Model\Character $c): string => $c->name, $players->wanted()));
        self::assertTrue($players->wanted()[0]->online);
        self::assertSame(1, $players->countOnline(601));

        // Job 2 must not match job 24 ("CONCAT(';', jobs) LIKE '%;2,%'").
        self::assertSame(['Iopette' => [24 => 140, 2 => 50], 'Cra' => [2 => 999]], $players->jobsOfCharactersWithJob(2));
        self::assertSame(['Iopette'], array_keys($players->jobsOfCharactersWithJob(24)));
    }

    public function testDropsAndMaps(): void
    {
        $this->insert($this->game(), 'monsters', ['id' => 101, 'name' => 'Bouftou', 'gfxID' => 1, 'align' => -1, 'grades' => '', 'colors' => '', 'stats' => '', 'statsInfos' => '', 'spells' => '', 'pdvs' => '', 'points' => '', 'inits' => '', 'minKamas' => 0, 'maxKamas' => 0, 'exps' => '', 'AI_Type' => 1, 'capturable' => 1, 'type' => 1, 'aggroDistance' => 1, 'isBoss' => 0, 'isArchmonster' => 0]);
        $this->insert($this->game(), 'item_template', ['id' => 385, 'type' => 35, 'name' => 'Laine de Bouftou', 'level' => 1, 'statsTemplate' => '', 'pod' => 1, 'panoplie' => -1, 'prix' => 1, 'conditions' => '', 'armesInfos' => '']);
        $this->insert($this->game(), 'drops', ['monsterName' => 'Bouftou', 'monsterId' => 101, 'objectName' => 'Laine', 'objectId' => 385, 'percentGrade1' => 60, 'percentGrade2' => 65, 'percentGrade3' => 70, 'percentGrade4' => 75, 'percentGrade5' => 80, 'ceil' => 0, 'action' => '-1', 'level' => 1]);
        $this->insert($this->game(), 'maps', ['id' => 7411, 'date' => '', 'width' => 15, 'heigth' => 17, 'places' => '', 'key' => '', 'mapData' => '', 'monsters' => '', 'capabilities' => 0, 'mappos' => '-9,-12,537', 'numgroup' => 3, 'minSize' => 1, 'fixSize' => -1, 'maxSize' => 8, 'sniffed' => 0]);
        $this->insert($this->login(), 'world_base_sub_areas', ['id' => 537, 'area' => 1, 'name' => 'Plaine des Bouftous']);

        $game = new GameRepository($this->database);
        $drops = $game->searchDrops('monster', 'BOUF');
        self::assertCount(1, $drops);
        self::assertSame('Laine de Bouftou', $drops[0]->itemName);
        self::assertSame(80.0, $drops[0]->maxRate);
        self::assertCount(1, $game->searchDrops('item', 'laine'));
        self::assertSame([], $game->searchDrops('item', 'épée'));

        self::assertSame(537, $game->mapPosition(7411)?->subAreaId);
        self::assertNull($game->mapPosition(1));
        self::assertSame('Plaine des Bouftous', $game->subAreaName(537));
    }

    public function testSidebarShowsPositionsOnlyWhenAllowed(): void
    {
        $this->testDropsAndMaps();
        $hidden = $this->createAccount('alice');
        $shown = $this->createAccount('bob', '', ['showOrHidePos' => 1]);
        $base = ['color1' => 0, 'color2' => 0, 'color3' => 0, 'kamas' => 0, 'spellboost' => 0, 'capital' => 0, 'size' => 100, 'gfx' => 80, 'map' => 7411, 'cell' => 1, 'spells' => '', 'objets' => '', 'storeObjets' => '', 'server' => 601, 'sexe' => 0, 'class' => 9, 'groupe' => -1, 'jobs' => ''];
        $this->insert($this->login(), 'world_players', $base + ['account' => $hidden, 'name' => 'Cache', 'level' => 20, 'xp' => 200_000]);
        $this->insert($this->login(), 'world_players', $base + ['account' => $shown, 'name' => 'Visible', 'level' => 10, 'xp' => 100_000]);

        $data = $this->container()->get(Sidebar::class)->data();
        self::assertSame(['Cache', 'Visible'], array_map(static fn (\StarLoco\Web\Model\LocatedCharacter $e): string => $e->character->name, $data->podium));
        self::assertNull($data->podium[0]->place);
        $place = $data->podium[1]->place;
        self::assertNotNull($place);
        self::assertSame('Plaine des Bouftous', $place->name);
        self::assertSame(-12, $place->y);
        self::assertFalse($data->loginOnline, 'nothing listens on the test ports');
        self::assertSame(2, $data->stats->accounts);
    }
}
