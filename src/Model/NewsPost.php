<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

use DateTimeImmutable;

/** Website news (starloco_login.website_timeline_news), also served to the launcher. */
final readonly class NewsPost
{
    public function __construct(
        public int $id,
        public string $title,
        /** HTML written by an administrator. */
        public string $content,
        public string $author,
        public DateTimeImmutable $publishedAt,
        public string $image,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            (int) $row->id,
            (string) $row->title,
            (string) $row->content,
            (string) $row->author,
            new DateTimeImmutable((string) $row->date),
            (string) $row->img,
        );
    }
}
