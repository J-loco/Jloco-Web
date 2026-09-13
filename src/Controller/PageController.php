<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Service\ForumFeed;

/** Mostly static pages. */
final class PageController extends AbstractController
{
    /** @param array<string, string> $params */
    public function join(Request $request, array $params): Response
    {
        return $this->render('pages/join.html.twig');
    }

    /** @param array<string, string> $params */
    public function terms(Request $request, array $params): Response
    {
        return $this->render('pages/terms.html.twig');
    }

    /** @param array<string, string> $params */
    public function forumNews(Request $request, array $params): Response
    {
        $feed = $this->get(ForumFeed::class);
        return $this->render('pages/forum_news.html.twig', [
            'configured' => $feed->isConfigured(),
            'items' => $feed->latest(),
        ]);
    }
}
