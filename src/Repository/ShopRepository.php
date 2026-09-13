<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;

/**
 * Shop catalogue and purchase logs (starloco_login.website_shop_*).
 *
 * website_shop_objects.server is a game-database index (1 = the configured game DB), not a
 * world_servers id; see docs/refactor/audit.md (D13).
 */
final class ShopRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    /** Servers having active items, with a display name when it matches a world_servers id. @return list<object{id: int, name: ?string}> */
    public function servers(): array
    {
        return $this->database->login()->query(
            'SELECT DISTINCT o.server AS id, s.name FROM website_shop_objects o LEFT JOIN world_servers s ON s.id = o.server WHERE o.active = 1 ORDER BY o.server'
        )->fetchAll();
    }

    /** @return list<object{id: int, name: string, items: int}> */
    public function categories(int $server): array
    {
        $query = $this->database->login()->prepare(
            'SELECT c.id, c.name, COUNT(*) AS items FROM website_shop_categories c JOIN website_shop_objects o ON o.category = c.id
             WHERE c.active = 1 AND o.active = 1 AND o.server = ? GROUP BY c.id, c.name ORDER BY c.name'
        );
        $query->execute([$server]);
        return $query->fetchAll();
    }

    /** @return list<object{template: int, name: string, price: int, jp: int, level: int, effects: string}> */
    public function items(int $server, int $category): array
    {
        $query = $this->database->login()->prepare(
            'SELECT o.template, o.name, o.price, o.jp, t.level, t.effects FROM website_shop_objects o JOIN website_shop_objects_templates t ON t.id = o.template
             WHERE o.server = ? AND o.category = ? AND o.active = 1 ORDER BY o.price DESC'
        );
        $query->execute([$server, $category]);
        return $query->fetchAll();
    }

    public function findItem(int $server, int $template): ?object
    {
        $query = $this->database->login()->prepare(
            'SELECT o.template, o.name, o.price, o.jp, o.category, c.name AS category_name, t.level, t.effects, t.description
             FROM website_shop_objects o
             JOIN website_shop_objects_templates t ON t.id = o.template
             LEFT JOIN website_shop_categories c ON c.id = o.category
             WHERE o.server = ? AND o.template = ? AND o.active = 1'
        );
        $query->execute([$server, $template]);
        return $query->fetch() ?: null;
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
