<?php

declare(strict_types=1);

namespace StarLoco\Web\Tests\Integration;

use StarLoco\Web\Repository\ShopRepository;
use StarLoco\Web\Service\ShopService;

final class ShopServiceTest extends IntegrationTestCase
{
    private int $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = $this->createAccount('alice', '', ['points' => 100]);
        $this->insert($this->login(), 'website_shop_categories', ['id' => 12, 'name' => 'Familiers', 'active' => 1]);
        $this->insert($this->login(), 'website_shop_objects_templates', ['id' => 8949, 'type' => 18, 'name' => 'Bwak de Feu', 'description' => 'Un familier.', 'skin' => 8007, 'level' => 1, 'effects' => '7d#a#14#0#1d10+9']);
        $this->insert($this->login(), 'website_shop_objects', ['name' => 'Bwak de Feu', 'template' => 8949, 'jp' => 1, 'price' => 60, 'category' => 12, 'server' => '1', 'active' => 1]);
        $this->insert($this->game(), 'item_template', ['id' => 8949, 'type' => 18, 'name' => 'Bwak de Feu']);
    }

    public function testCatalogue(): void
    {
        $shop = new ShopRepository($this->database);

        self::assertSame([1 => null], $shop->servers());
        self::assertSame(12, $shop->categories(1)[0]->id);
        self::assertSame(1, $shop->categories(1)[0]->itemCount);

        $item = $shop->findItem(1, 8949);
        self::assertNotNull($item);
        self::assertSame(60, $item->price);
        self::assertTrue($item->maxStats);
        self::assertSame('Familiers', $item->categoryName);
        self::assertSame(18, $item->type);
        self::assertSame(8007, $item->skin);
        self::assertSame([['type' => 18, 'skin' => 8007]], $shop->sprites());
        self::assertNull($shop->findItem(2, 8949), 'other server');
    }

    public function testPurchaseDebitsDeliversAndLogs(): void
    {
        $service = $this->container()->get(ShopService::class);

        self::assertNull($service->purchase($this->accountId, 1, 8949));
        self::assertSame(40, (int) $this->scalar($this->login(), 'SELECT points FROM world_accounts WHERE guid = ?', [$this->accountId]));
        self::assertSame('8949,1,1', $this->scalar($this->game(), 'SELECT objects FROM gifts WHERE id = ?', [$this->accountId]));
        self::assertSame(1, (int) $this->scalar($this->login(), 'SELECT COUNT(*) FROM website_shop_objects_purchases WHERE account = ? AND template = 8949', [$this->accountId]));

        $this->login()->exec('UPDATE world_accounts SET points = 60');
        self::assertNull($service->purchase($this->accountId, 1, 8949));
        self::assertSame('8949,1,1;8949,1,1', $this->scalar($this->game(), 'SELECT objects FROM gifts WHERE id = ?', [$this->accountId]), 'gifts are appended like Account.addGift');
    }

    public function testLegacyShopRewardUsesClientKnownDeliveryTemplate(): void
    {
        $this->insert($this->login(), 'website_shop_objects_templates', ['id' => 26005, 'type' => 15, 'name' => 'Coffre Légendaire', 'skin' => 13, 'level' => 1]);
        $this->insert($this->login(), 'website_shop_objects', ['name' => 'Coffre Légendaire', 'template' => 26005, 'jp' => 0, 'price' => 60, 'category' => 12, 'server' => '1', 'active' => 1]);
        $this->insert($this->game(), 'item_template', ['id' => 12839, 'type' => 89, 'name' => 'Gemme Spirituelle Emballée']);

        $service = $this->container()->get(ShopService::class);

        self::assertNull($service->purchase($this->accountId, 1, 26005));
        self::assertSame(40, (int) $this->scalar($this->login(), 'SELECT points FROM world_accounts WHERE guid = ?', [$this->accountId]));
        self::assertSame('12839,1,0', $this->scalar($this->game(), 'SELECT objects FROM gifts WHERE id = ?', [$this->accountId]));
        self::assertSame(1, (int) $this->scalar($this->login(), 'SELECT COUNT(*) FROM website_shop_objects_purchases WHERE template = 26005'));
    }

    public function testRefusesWhenPointsAreMissingOrItemUnavailable(): void
    {
        $service = $this->container()->get(ShopService::class);
        $this->login()->exec('UPDATE world_accounts SET points = 59');

        self::assertSame('Tu n\'as pas assez de points pour acheter cet objet.', $service->purchase($this->accountId, 1, 8949));
        self::assertSame('Cet objet n\'est pas en vente.', $service->purchase($this->accountId, 1, 1));
        self::assertSame(59, (int) $this->scalar($this->login(), 'SELECT points FROM world_accounts WHERE guid = ?', [$this->accountId]));
        self::assertFalse($this->scalar($this->game(), 'SELECT objects FROM gifts WHERE id = ?', [$this->accountId]));
    }

    public function testServersWithoutGameDatabaseAreNotForSale(): void
    {
        $service = $this->container(['shopServers' => []])->get(ShopService::class);
        self::assertFalse($service->isDeliverable(1));
        self::assertSame('Cet objet n\'est pas en vente.', $service->purchase($this->accountId, 1, 8949));
    }

    public function testMissingGameTemplateDoesNotDebitOrQueueGift(): void
    {
        $service = $this->container()->get(ShopService::class);
        $this->game()->exec('DELETE FROM item_template WHERE id = 8949');

        $errorLog = ini_set('error_log', '/dev/null');
        try {
            self::assertSame('Cet objet est temporairement indisponible.', $service->purchase($this->accountId, 1, 8949));
        } finally {
            ini_set('error_log', (string) $errorLog);
        }

        self::assertSame(100, (int) $this->scalar($this->login(), 'SELECT points FROM world_accounts WHERE guid = ?', [$this->accountId]));
        self::assertFalse($this->scalar($this->game(), 'SELECT objects FROM gifts WHERE id = ?', [$this->accountId]));
        self::assertSame(0, (int) $this->scalar($this->login(), 'SELECT COUNT(*) FROM website_shop_objects_purchases'));
    }

    public function testUnavailableGameDatabaseDoesNotDebitPoints(): void
    {
        $service = $this->container(['shopServers' => [1 => 'starloco_database_that_does_not_exist']])->get(ShopService::class);

        $errorLog = ini_set('error_log', '/dev/null'); // the failure is logged on purpose
        try {
            self::assertSame('La boutique est temporairement indisponible.', $service->purchase($this->accountId, 1, 8949));
        } finally {
            ini_set('error_log', (string) $errorLog);
        }
        self::assertSame(100, (int) $this->scalar($this->login(), 'SELECT points FROM world_accounts WHERE guid = ?', [$this->accountId]));
        self::assertSame(0, (int) $this->scalar($this->login(), 'SELECT COUNT(*) FROM website_shop_objects_purchases'));
    }
}
