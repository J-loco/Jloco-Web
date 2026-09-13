<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** An item for sale (website_shop_objects joined with its template). */
final readonly class ShopItem
{
    public function __construct(
        public int $template,
        public string $name,
        public int $price,
        /** Delivered with perfect stats ("jet parfait"). */
        public bool $maxStats,
        public int $level,
        /** Raw effects string, see Game\ItemEffects. */
        public string $effects,
        /** website_shop_objects_templates.type and .skin: the item sprite (public/assets/img/items). */
        public int $type = 0,
        public int $skin = 0,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?string $description = null,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            template: (int) $row->template,
            name: (string) $row->name,
            price: (int) $row->price,
            maxStats: (bool) $row->jp,
            level: (int) $row->level,
            effects: (string) $row->effects,
            type: (int) ($row->type ?? 0),
            skin: (int) ($row->skin ?? 0),
            categoryId: isset($row->category) ? (int) $row->category : null,
            categoryName: isset($row->category_name) ? (string) $row->category_name : null,
            description: isset($row->description) && $row->description !== '' ? (string) $row->description : null,
        );
    }
}
