<?php

declare(strict_types=1);

namespace JLoco\Web\Controller;

use JLoco\Web\Config;
use JLoco\Web\Http\Request;
use JLoco\Web\Http\Response;
use JLoco\Web\Model\Account;
use JLoco\Web\Repository\NewsRepository;
use JLoco\Web\Support\Text;

final class AdminController extends AbstractController
{
    /** News tables are latin1 (login database). */
    private const string UNSUPPORTED_CHARACTERS = 'Caractères non pris en charge (émojis, alphabets non latins) : les tables de nouvelles sont en latin1.';

    /** @param array<string, string> $params */
    public function index(Request $request, array $params): Response
    {
        if ($this->admin() === null) {
            return $this->notFound();
        }
        $news = $this->get(NewsRepository::class);
        return $this->render('pages/admin.html.twig', [
            'news' => $news->latest(100),
            'gameNews' => $news->gameNews(),
            'gameNewsTypes' => NewsRepository::GAME_NEWS_TYPES,
        ]);
    }

    /** @param array<string, string> $params */
    public function createNews(Request $request, array $params): Response
    {
        $admin = $this->admin();
        if ($admin === null) {
            return $this->notFound();
        }
        $title = trim($request->input('title'));
        $content = trim($request->input('content'));
        if ($title === '' || $content === '' || mb_strlen($title) > 100) {
            $this->flash('danger', 'Le titre (100 caractères max.) et le contenu sont obligatoires.');
        } elseif (!Text::fitsLatin1($title) || !Text::fitsLatin1($content)) {
            $this->flash('danger', self::UNSUPPORTED_CHARACTERS);
        } else {
            $this->get(NewsRepository::class)->create($admin->pseudo ?? $this->get(Config::class)->siteName, $title, $content); // never the account name (a credential)
            $this->flash('success', 'Nouvelle publiée.');
        }
        return $this->redirectTo('admin');
    }

    /** @param array<string, string> $params */
    public function deleteNews(Request $request, array $params): Response
    {
        if ($this->admin() === null) {
            return $this->notFound();
        }
        $this->get(NewsRepository::class)->delete((int) $params['id']);
        $this->flash('success', 'Nouvelle supprimée.');
        return $this->redirectTo('admin');
    }

    /** @param array<string, string> $params */
    public function createGameNews(Request $request, array $params): Response
    {
        if ($this->admin() === null) {
            return $this->notFound();
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

    /** @param array<string, string> $params */
    public function deleteGameNews(Request $request, array $params): Response
    {
        if ($this->admin() === null) {
            return $this->notFound();
        }
        $this->get(NewsRepository::class)->deleteGameNews((int) $params['id']);
        $this->flash('success', 'Nouvelle en jeu supprimée.');
        return Response::redirect($this->url('admin') . '#game-news');
    }

    /** The logged-in administrator; non-admins get a 404, so the admin area is not advertised. */
    private function admin(): ?Account
    {
        return $this->auth()->isAdmin() ? $this->auth()->account() : null;
    }
}
