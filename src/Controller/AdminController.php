<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Repository\NewsRepository;
use StarLoco\Web\Support\Text;

final class AdminController extends AbstractController
{
    /** News tables are latin1 (login database). */
    private const UNSUPPORTED_CHARACTERS = 'Caractères non pris en charge (émojis, alphabets non latins) : les tables de nouvelles sont en latin1.';

    public function index(Request $request, array $params): Response
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        $news = $this->get(NewsRepository::class);
        return $this->render('pages/admin.html.twig', [
            'news' => $news->latest(100),
            'gameNews' => $news->gameNews(),
            'gameNewsTypes' => NewsRepository::GAME_NEWS_TYPES,
        ]);
    }

    public function createNews(Request $request, array $params): Response
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        $title = trim($request->input('title'));
        $content = trim($request->input('content'));
        if ($title === '' || $content === '' || mb_strlen($title) > 100) {
            $this->flash('danger', 'Le titre (100 caractères max.) et le contenu sont obligatoires.');
        } elseif (!Text::fitsLatin1($title) || !Text::fitsLatin1($content)) {
            $this->flash('danger', self::UNSUPPORTED_CHARACTERS);
        } else {
            $this->get(NewsRepository::class)->create((string) $this->auth()->account()->account, $title, $content);
            $this->flash('success', 'Nouvelle publiée.');
        }
        return $this->redirectTo('admin');
    }

    public function deleteNews(Request $request, array $params): Response
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        $this->get(NewsRepository::class)->delete((int) $params['id']);
        $this->flash('success', 'Nouvelle supprimée.');
        return $this->redirectTo('admin');
    }

    public function createGameNews(Request $request, array $params): Response
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        $title = trim($request->input('title'));
        $type = $request->input('type');
        if ($title === '' || mb_strlen($title) > 50 || !isset(NewsRepository::GAME_NEWS_TYPES[$type])) {
            $this->flash('danger', 'Le titre (50 caractères max.) et le type sont obligatoires.');
        } elseif (!Text::fitsLatin1($title)) {
            $this->flash('danger', self::UNSUPPORTED_CHARACTERS);
        } else {
            $this->get(NewsRepository::class)->createGameNews($title, $type);
            $this->flash('success', 'Nouvelle en jeu publiée.');
        }
        return Response::redirect($this->url('admin') . '#game-news');
    }

    public function deleteGameNews(Request $request, array $params): Response
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }
        $this->get(NewsRepository::class)->deleteGameNews((int) $params['id']);
        $this->flash('success', 'Nouvelle en jeu supprimée.');
        return Response::redirect($this->url('admin') . '#game-news');
    }

    /** Non-admins get a 404, so the admin area is not advertised. */
    private function denyUnlessAdmin(): ?Response
    {
        return $this->auth()->isAdmin() ? null : $this->notFound();
    }
}
