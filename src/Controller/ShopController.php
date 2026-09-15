<?php

declare(strict_types=1);

namespace JLoco\Web\Controller;

use JLoco\Web\Config;
use JLoco\Web\Http\Request;
use JLoco\Web\Http\Response;
use JLoco\Web\Repository\ShopRepository;
use JLoco\Web\Service\ShopService;

final class ShopController extends AbstractController
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params): Response
    {
        if ($this->auth()->account() === null) {
            return $this->loginRedirect();
        }
        $servers = $this->servers();
        if (count($servers) === 1) {
            return Response::redirect($this->url('shop_server', ['server' => array_key_first($servers)]), 302);
        }
        return $this->render('pages/shop.html.twig', ['servers' => $servers, 'server' => null, 'categories' => [], 'category' => null, 'items' => []]);
    }

    /** @param array<string, string> $params */
    public function server(Request $request, array $params): Response
    {
        return $this->showCategory((int) $params['server'], null);
    }

    /** @param array<string, string> $params */
    public function category(Request $request, array $params): Response
    {
        return $this->showCategory((int) $params['server'], (int) $params['category']);
    }

    /** @param array<string, string> $params */
    public function item(Request $request, array $params): Response
    {
        if ($this->auth()->account() === null) {
            return $this->loginRedirect();
        }
        $server = (int) $params['server'];
        $servers = $this->servers();
        $item = isset($servers[$server]) ? $this->get(ShopRepository::class)->findItem($server, (int) $params['template']) : null;
        if ($item === null) {
            return $this->notFound();
        }
        return $this->render('pages/shop_item.html.twig', ['item' => $item, 'server' => $server, 'serverName' => $servers[$server]]);
    }

    /** @param array<string, string> $params */
    public function buy(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        if ($account === null) {
            return $this->loginRedirect();
        }
        $route = ['server' => (int) $params['server'], 'template' => (int) $params['template']];
        $error = $this->get(ShopService::class)->purchase($account->id, $route['server'], $route['template']);

        if ($error !== null) {
            $this->flash('danger', $error);
        } else {
            $this->flash('success', 'Achat effectué : ton objet t\'attend en jeu à ta prochaine connexion.');
        }
        return $this->redirectTo('shop_item', $route);
    }

    private function showCategory(int $server, ?int $categoryId): Response
    {
        if ($this->auth()->account() === null) {
            return $this->loginRedirect();
        }
        $servers = $this->servers();
        if (!isset($servers[$server])) {
            return $this->notFound();
        }

        $shop = $this->get(ShopRepository::class);
        $categories = $shop->categories($server);
        $current = null;
        foreach ($categories as $category) {
            if ($category->id === $categoryId) {
                $current = $category;
            }
        }
        if ($categoryId !== null && $current === null) {
            return $this->notFound();
        }

        return $this->render('pages/shop.html.twig', [
            'servers' => $servers,
            'server' => $server,
            'categories' => $categories,
            'category' => $current,
            'items' => $current !== null ? $shop->items($server, $current->id) : [],
        ]);
    }

    /**
     * Shop servers that have items and a configured game database, with their display name.
     *
     * @return array<int, string>
     */
    private function servers(): array
    {
        $shopService = $this->get(ShopService::class);
        $siteName = $this->get(Config::class)->siteName;
        $servers = [];
        foreach ($this->get(ShopRepository::class)->servers() as $id => $name) {
            if ($shopService->isDeliverable($id)) {
                $servers[$id] = $name ?? $siteName;
            }
        }
        return $servers;
    }
}
