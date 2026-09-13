<?php

declare(strict_types=1);

namespace StarLoco\Web\Repository;

use StarLoco\Web\Database;
use StarLoco\Web\Model\GameNews;
use StarLoco\Web\Model\NewsPost;

/**
 * Website news (website_timeline_news, also served to the launcher) and in-game news
 * (client_rss_news, read by the Dofus client). Both live in starloco_login.
 */
final readonly class NewsRepository
{
    /** client_rss_news.icon values understood by the client, with their label. */
    public const array GAME_NEWS_TYPES = ['News' => 'Nouvelle', 'Event' => 'Événement', 'Update_fr' => 'Mise à jour', 'Maintenance' => 'Maintenance'];

    public function __construct(private Database $database)
    {
    }

    public function count(): int
    {
        return (int) $this->database->login()->query('SELECT COUNT(*) FROM website_timeline_news')->fetchColumn();
    }

    /** @return list<NewsPost> */
    public function latest(int $limit, int $offset = 0): array
    {
        $query = $this->database->login()->prepare('SELECT id, author, title, content, date, img FROM website_timeline_news ORDER BY id DESC LIMIT ? OFFSET ?');
        $query->execute([$limit, $offset]);
        return array_map(NewsPost::fromRow(...), $query->fetchAll());
    }

    public function create(string $author, string $title, string $content): int
    {
        $login = $this->database->login();
        // The _en/_es columns have no default: reuse the French text until translations exist.
        $login
            ->prepare('INSERT INTO website_timeline_news (author, title, title_en, title_es, content, content_en, content_es, date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$author, $title, $title, $title, $content, $content, $content]);
        return (int) $login->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->database->login()->prepare('DELETE FROM website_timeline_news WHERE id = ?')->execute([$id]);
    }

    /** @return list<GameNews> */
    public function gameNews(): array
    {
        return array_map(GameNews::fromRow(...), $this->database->login()->query('SELECT id, title_fr, icon, date FROM client_rss_news ORDER BY id DESC')->fetchAll());
    }

    public function createGameNews(string $title, string $type): void
    {
        // client_rss_news.id is not AUTO_INCREMENT and link has no default.
        $this->database->login()
            ->prepare("INSERT INTO client_rss_news (id, title_fr, title_en, date, icon, link) SELECT COALESCE(MAX(id), 0) + 1, ?, ?, ?, ?, '' FROM client_rss_news")
            ->execute([$title, $title, (int) date('Ymd'), $type]);
    }

    public function deleteGameNews(int $id): void
    {
        $this->database->login()->prepare('DELETE FROM client_rss_news WHERE id = ?')->execute([$id]);
    }
}
