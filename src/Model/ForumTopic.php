<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

use DateTimeImmutable;

/** A topic of the forum RSS feed (untrusted text, escaped by templates). */
final readonly class ForumTopic
{
    public function __construct(
        public string $title,
        /** http(s) link, null otherwise. */
        public ?string $link,
        public string $summary,
        public ?DateTimeImmutable $publishedAt,
    ) {
    }
}
