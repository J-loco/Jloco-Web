<?php

declare(strict_types=1);

/**
 * News feed for the StarLoco launcher. URL contract, do not move.
 * GET [?limit=10] -> [{"id":int,"title":string,"content":string,"date":string,"author":string,"img":string}]
 *
 * Same table as the site's news (administration page), so news is written once for both.
 */

use StarLoco\Web\Repository\NewsRepository;

$container = require dirname(__DIR__, 2) . '/config/container.php';
$limit = isset($_GET['limit']) && is_string($_GET['limit']) ? max(1, min(30, (int) $_GET['limit'])) : 10;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

try {
    $items = array_map(static fn (object $row) => [
        'id' => (int) $row->id,
        'title' => $row->title,
        'content' => strip_tags($row->content),
        'date' => $row->date,
        'author' => $row->author,
        'img' => $row->img,
    ], $container->get(NewsRepository::class)->latest($limit));
    echo json_encode($items, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('StarLoco-Web launcher/news: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'news unavailable']);
}
