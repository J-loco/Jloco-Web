<?php

declare(strict_types=1);

namespace JLoco\Web\Model;

/** In-game news (jloco_login.client_rss_news), read by the Dofus client. */
final readonly class GameNews
{
    public function __construct(
        public int $id,
        public string $title,
        /** Icon key understood by the client, see NewsRepository::GAME_NEWS_TYPES. */
        public string $type,
        /** Ymd as an integer. */
        public int $date,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self((int) $row->id, (string) $row->title_fr, (string) $row->icon, (int) $row->date);
    }
}
