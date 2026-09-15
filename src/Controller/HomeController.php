<?php

declare(strict_types=1);

namespace JLoco\Web\Controller;

use JLoco\Web\Http\Request;
use JLoco\Web\Http\Response;
use JLoco\Web\Repository\NewsRepository;

final class HomeController extends AbstractController
{
    private const int PER_PAGE = 10;

    /** @param array<string, string> $params */
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
