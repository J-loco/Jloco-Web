<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Config;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Repository\ShopRepository;
use StarLoco\Web\Service\ShopService;

final class ShopController extends AbstractController
{
    public function index(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $servers = $this->servers();
        if (count($servers) === 1) {
            return Response::redirect($this->url('shop_server', ['server' => array_key_first($servers)]), 302);
        }
        return $this->render('pages/shop.html.twig', ['servers' => $servers, 'server' => null, 'categories' => [], 'category' => null, 'items' => []]);
    }

    public function server(Request $request, array $params): Response
    {
        return $this->category($request, $params + ['category' => null]);
    }

    public function category(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $shop = $this->get(ShopRepository::class);
        $servers = $this->servers();
        $server = (int) $params['server'];
        if (!isset($servers[$server])) {
            return $this->notFound();
        }

        $categories = $shop->categories($server);
        $category = $params['category'] !== null ? (int) $params['category'] : null;
        $current = null;
        foreach ($categories as $row) {
            if ((int) $row->id === $category) {
                $current = $row;
            }
        }
        if ($category !== null && $current === null) {
            return $this->notFound();
        }

        return $this->render('pages/shop.html.twig', [
            'servers' => $servers,
            'server' => $server,
            'categories' => $categories,
            'category' => $current,
            'items' => $current !== null ? $shop->items($server, $category) : [],
        ]);
    }

    public function item(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $server = (int) $params['server'];
        $item = $this->get(ShopRepository::class)->findItem($server, (int) $params['template']);
        if ($item === null) {
            return $this->notFound();
        }
        return $this->render('pages/shop_item.html.twig', ['item' => $item, 'server' => $server, 'serverName' => $this->servers()[$server] ?? '']);
    }

    public function buy(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $server = (int) $params['server'];
        $template = (int) $params['template'];
        $error = $this->get(ShopService::class)->purchase((int) $this->auth()->accountId(), $server, $template);

        if ($error !== null) {
            $this->flash('danger', $error);
            return $this->redirectTo('shop_item', ['server' => $server, 'template' => $template]);
        }
        $this->flash('success', 'Achat effectué : ton objet t\'attend en jeu à ta prochaine connexion.');
        return $this->redirectTo('shop_item', ['server' => $server, 'template' => $template]);
    }

    /** @return array<int, string> server id => display name */
    private function servers(): array
    {
        $servers = [];
        foreach ($this->get(ShopRepository::class)->servers() as $row) {
            $servers[(int) $row->id] = $row->name ?: $this->get(Config::class)->siteName;
        }
        return $servers;
    }
}
