<?php
/**
 * News feed for the StarLoco launcher.
 * GET [?limit=10] -> [{"id":int,"title":string,"content":string,"date":string,"author":string,"img":string}]
 *
 * Reads website_timeline_news — the same table the portal's administration page
 * writes to, so posting news once feeds both the site and the launcher.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../configuration/configuration.php');

$limit = isset($_GET['limit']) ? max(1, min(30, (int) $_GET['limit'])) : 10;

$items = array();
try {
    $query = $connection->prepare('SELECT `id`, `author`, `title`, `content`, `date`, `img` FROM `website_timeline_news` ORDER BY `id` DESC LIMIT ' . $limit . ';');
    $query->execute();
    $query->setFetchMode(PDO::FETCH_OBJ);
    while ($row = $query->fetch()) {
        $items[] = array(
            'id'      => (int) $row->id,
            'title'   => utf8_encode($row->title),
            'content' => utf8_encode(strip_tags($row->content)),
            'date'    => $row->date,
            'author'  => utf8_encode($row->author),
            'img'     => $row->img
        );
    }
    $query->closeCursor();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => 'news unavailable'));
    return;
}

echo json_encode($items);
