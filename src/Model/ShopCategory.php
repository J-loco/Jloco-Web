<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** A shop category with the number of active items on a server. */
final readonly class ShopCategory
{
    public function __construct(
        public int $id,
        public string $name,
        public int $itemCount,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self((int) $row->id, (string) $row->name, (int) $row->items);
    }
}
