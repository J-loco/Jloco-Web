<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Model\ShopCategory;
use StarLoco\Web\Model\ShopItem;

/**
 * Shop catalogue and purchase logs (starloco_login.website_shop_*).
 *
 * website_shop_objects.server is a shop server key (see Config::$shopServers), not necessarily a
 * world_servers id; see docs/refactor/audit.md (D13).
 */
final readonly class ShopRepository
{
    private const string ITEM_COLUMNS = 'o.template, o.name, o.price, o.jp, o.category, t.level, t.effects, t.type, t.skin';

    public function __construct(private Database $database)
    {
    }

    /**
     * Shop server keys having active items, with the world_servers name when the key matches one.
     *
     * @return array<int, ?string>
     */
    public function servers(): array
    {
        $servers = [];
        foreach ($this->database->login()->query(
            'SELECT DISTINCT o.server AS id, s.name FROM website_shop_objects o LEFT JOIN world_servers s ON s.id = o.server WHERE o.active = 1 ORDER BY o.server',
        )->fetchAll() as $row) {
            $servers[(int) $row->id] = $row->name !== null ? (string) $row->name : null;
        }
        return $servers;
    }

    /** @return list<ShopCategory> */
    public function categories(int $server): array
    {
        $query = $this->database->login()->prepare(
            'SELECT c.id, c.name, COUNT(*) AS items FROM website_shop_categories c JOIN website_shop_objects o ON o.category = c.id
             WHERE c.active = 1 AND o.active = 1 AND o.server = ? GROUP BY c.id, c.name ORDER BY c.name',
        );
        $query->execute([$server]);
        return array_map(ShopCategory::fromRow(...), $query->fetchAll());
    }

    /** @return list<ShopItem> */
    public function items(int $server, int $category): array
    {
        $query = $this->database->login()->prepare(
            'SELECT ' . self::ITEM_COLUMNS . ' FROM website_shop_objects o JOIN website_shop_objects_templates t ON t.id = o.template
             WHERE o.server = ? AND o.category = ? AND o.active = 1 ORDER BY o.price DESC',
        );
        $query->execute([$server, $category]);
        return array_map(ShopItem::fromRow(...), $query->fetchAll());
    }

    public function findItem(int $server, int $template): ?ShopItem
    {
        $query = $this->database->login()->prepare(
            'SELECT ' . self::ITEM_COLUMNS . ', c.name AS category_name, t.description
             FROM website_shop_objects o
             JOIN website_shop_objects_templates t ON t.id = o.template
             LEFT JOIN website_shop_categories c ON c.id = o.category
             WHERE o.server = ? AND o.template = ? AND o.active = 1',
        );
        $query->execute([$server, $template]);
        $row = $query->fetch();
        return $row ? ShopItem::fromRow($row) : null;
    }

    /**
     * Every item template of the catalogue, to export their sprites (bin/export-item-images).
     *
     * @return list<array{type: int, skin: int}>
     */
    public function sprites(): array
    {
        return array_map(
            static fn (object $row): array => ['type' => (int) $row->type, 'skin' => (int) $row->skin],
            $this->database->login()->query('SELECT DISTINCT t.type, t.skin FROM website_shop_objects_templates t JOIN website_shop_objects o ON o.template = t.id WHERE t.skin > 0')->fetchAll(),
        );
    }

    public function logPurchase(int $accountId, int $template, int $server): void
    {
        $this->database->login()
            ->prepare('INSERT INTO website_shop_objects_purchases (account, template, quantite, server, date) VALUES (?, ?, 1, ?, NOW())')
            ->execute([$accountId, $template, $server]);
    }

    public function logPointsPurchase(string $account, int $points, string $code, string $country, string $type): void
    {
        $this->database->login()
            ->prepare('INSERT INTO website_shop_points_purchases (account, points, code, pays, type, date) VALUES (?, ?, ?, ?, ?, NOW())')
            ->execute([$account, $points, $code, $country, $type]);
    }
}
