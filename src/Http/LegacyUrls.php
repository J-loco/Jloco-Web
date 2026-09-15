<?php

declare(strict_types=1);

namespace JLoco\Web\Http;

/**
 * 301 redirects from the old "index.php?page=<name>" URLs (bookmarks, forum posts, the launcher's
 * registerUrl) to the real paths.
 */
final readonly class LegacyUrls
{
    private const array PAGES = [
        'index' => 'home',
        'join' => 'join',
        'cgu' => 'terms',
        'news' => 'forum_news',
        'ladder' => 'ladder',
        'viewdrop' => 'drops',
        'vote' => 'vote',
        'signin' => 'login',
        'register' => 'register',
        'password' => 'password_reset',
        'profile' => 'account',
        'administration' => 'admin',
        'shop' => 'shop',
        'buy' => 'shop',
        'logout' => 'home',
    ];

    public function __construct(private Router $router)
    {
    }

    /** Target URL when $request is a legacy URL, null otherwise. */
    public function redirectFor(Request $request): ?string
    {
        if ($request->path !== '/' || $request->isPost()) {
            return null;
        }

        $page = $request->query('page');
        if ($page === '') {
            $num = $request->query('num');
            return ctype_digit($num) ? $this->router->url('home', ['p' => $num]) : null;
        }

        $server = $request->query('server');
        $category = $request->query('category');
        $template = $request->query('template');

        return match (true) {
            $page === 'shop' && ctype_digit($server) && ctype_digit($category) => $this->router->url('shop_category', ['server' => $server, 'category' => $category]),
            $page === 'shop' && ctype_digit($server) => $this->router->url('shop_server', ['server' => $server]),
            $page === 'buy' && ctype_digit($server) && ctype_digit($template) => $this->router->url('shop_item', ['server' => $server, 'template' => $template]),
            isset(self::PAGES[$page]) => $this->router->url(self::PAGES[$page]),
            default => null,
        };
    }
}
