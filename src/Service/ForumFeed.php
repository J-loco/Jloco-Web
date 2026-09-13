<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use DOMDocument;
use StarLoco\Web\Config;

/**
 * Latest topics from the forum RSS feed (FORUM_RSS_URL). Feed content is untrusted: only plain
 * text and http(s) links are kept, and templates escape everything.
 */
final class ForumFeed
{
    public function __construct(private readonly Config $config)
    {
    }

    public function isConfigured(): bool
    {
        return $this->config->forumRssUrl !== '';
    }

    /** @return list<object{title: string, link: ?string, summary: string, date: ?\DateTimeImmutable}> */
    public function latest(int $limit = 10): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $context = stream_context_create(['http' => ['timeout' => 5, 'user_agent' => 'StarLoco-Web']]);
        $xml = @file_get_contents($this->config->forumRssUrl, false, $context);
        $document = new DOMDocument();
        if ($xml === false || !@$document->loadXML($xml, LIBXML_NONET)) {
            return [];
        }

        $items = [];
        foreach ($document->getElementsByTagName('item') as $item) {
            $text = static fn (string $tag): string => trim((string) $item->getElementsByTagName($tag)->item(0)?->textContent);
            $link = $text('link');
            $summary = trim(html_entity_decode(strip_tags($text('description')), ENT_QUOTES, 'UTF-8'));
            $date = strtotime($text('pubDate'));

            $items[] = (object) [
                'title' => html_entity_decode(strip_tags($text('title')), ENT_QUOTES, 'UTF-8'),
                'link' => preg_match('#^https?://#i', $link) ? $link : null,
                'summary' => mb_strimwidth($summary, 0, 250, '…'),
                'date' => $date !== false ? (new \DateTimeImmutable())->setTimestamp($date) : null,
            ];
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }
}
