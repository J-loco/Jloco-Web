<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use DateTimeImmutable;
use DOMDocument;
use StarLoco\Web\Config;
use StarLoco\Web\Model\ForumTopic;

/**
 * Latest topics from the forum RSS feed (FORUM_RSS_URL). Feed content is untrusted: only plain
 * text and http(s) links are kept, and templates escape everything.
 */
final readonly class ForumFeed
{
    public function __construct(private Config $config)
    {
    }

    public function isConfigured(): bool
    {
        return $this->config->forumRssUrl !== '';
    }

    /** @return list<ForumTopic> */
    public function latest(int $limit = 10): array
    {
        if (!$this->isConfigured()) {
            return [];
        }
        $context = stream_context_create(['http' => ['timeout' => 5, 'user_agent' => 'StarLoco-Web']]);
        $xml = @file_get_contents($this->config->forumRssUrl, false, $context);
        return $xml === false ? [] : self::parse($xml, $limit);
    }

    /** @return list<ForumTopic> */
    public static function parse(string $xml, int $limit = 10): array
    {
        $document = new DOMDocument();
        if (!@$document->loadXML($xml, LIBXML_NONET)) {
            return [];
        }

        $topics = [];
        foreach ($document->getElementsByTagName('item') as $item) {
            $text = static fn (string $tag): string => trim((string) $item->getElementsByTagName($tag)->item(0)?->textContent);
            $link = $text('link');
            $summary = trim(html_entity_decode(strip_tags($text('description')), ENT_QUOTES, 'UTF-8'));
            $timestamp = strtotime($text('pubDate'));

            $topics[] = new ForumTopic(
                title: html_entity_decode(strip_tags($text('title')), ENT_QUOTES, 'UTF-8'),
                link: preg_match('#^https?://#i', $link) ? $link : null,
                summary: mb_strimwidth($summary, 0, 250, '…'),
                publishedAt: $timestamp !== false ? new DateTimeImmutable()->setTimestamp($timestamp) : null,
            );
            if (count($topics) >= $limit) {
                break;
            }
        }
        return $topics;
    }
}
