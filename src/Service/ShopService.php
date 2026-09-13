<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use PDOException;
use StarLoco\Web\Config;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Repository\GameRepository;
use StarLoco\Web\Repository\ShopRepository;

final readonly class ShopService
{
    public function __construct(
        private ShopRepository $shop,
        private AccountRepository $accounts,
        private GameRepository $game,
        private Config $config,
    ) {
    }

    /** Whether items of this shop server can be delivered (a game database is configured for it, SHOP_SERVERS). */
    public function isDeliverable(int $server): bool
    {
        return isset($this->config->shopServers[$server]);
    }

    /**
     * Buys one item. Points are debited atomically (no double spend) and refunded if the
     * delivery fails. Returns an error message, or null on success.
     */
    public function purchase(int $accountId, int $server, int $template): ?string
    {
        $item = $this->shop->findItem($server, $template);
        if ($item === null || !$this->isDeliverable($server)) {
            return 'Cet objet n\'est pas en vente.';
        }

        if (!$this->accounts->debitPoints($accountId, $item->price)) {
            return 'Tu n\'as pas assez de points pour acheter cet objet.';
        }

        try {
            $this->game->addGift($this->config->shopServers[$server], $accountId, $template, 1, $item->maxStats);
        } catch (PDOException $e) {
            $this->accounts->addPoints($accountId, $item->price);
            error_log('StarLoco-Web: gift delivery failed for account ' . $accountId . ': ' . $e->getMessage());
            return 'Une erreur s\'est produite, tes points ont été remboursés.';
        }

        $this->shop->logPurchase($accountId, $template, $server);
        return null;
    }
}
