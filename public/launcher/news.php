<?php

declare(strict_types=1);

/**
 * News feed for the JLoco launcher. URL contract, do not move.
 * GET [?limit=10] -> [{"id":int,"title":string,"content":string,"date":string,"author":string,"img":string}]
 *
 * Same table as the site's news (administration page), so news is written once for both.
 */

use JLoco\Web\Model\NewsPost;
use JLoco\Web\Repository\NewsRepository;

$container = require dirname(__DIR__, 2) . '/config/container.php';
$limit = isset($_GET['limit']) && is_string($_GET['limit']) ? max(1, min(30, (int) $_GET['limit'])) : 10;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

try {
    $items = array_map(static fn (NewsPost $post): array => [
        'id' => $post->id,
        'title' => $post->title,
        'content' => strip_tags($post->content),
        'date' => $post->publishedAt->format('Y-m-d H:i:s'),
        'author' => $post->author,
        'img' => $post->image,
    ], $container->get(NewsRepository::class)->latest($limit));
    echo json_encode($items, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('JLoco-Web launcher/news: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'news unavailable']);
}
