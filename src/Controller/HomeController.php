<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Repository\NewsRepository;

final class HomeController extends AbstractController
{
    private const PER_PAGE = 10;

    public function index(Request $request, array $params): Response
    {
        $news = $this->get(NewsRepository::class);
        $pages = max(1, (int) ceil($news->count() / self::PER_PAGE));
        $page = ctype_digit($request->query('p')) ? min(max(1, (int) $request->query('p')), $pages) : 1;

        return $this->render('pages/home.html.twig', [
            'news' => $news->latest(self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'page' => $page,
            'pages' => $pages,
        ]);
    }
}
