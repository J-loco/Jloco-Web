<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use PDOException;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Repository\GameRepository;
use StarLoco\Web\Repository\ShopRepository;

final class ShopService
{
    /** Game databases items can be delivered to (website_shop_objects.server). */
    public const DELIVERABLE_SERVERS = [1];

    public function __construct(
        private readonly ShopRepository $shop,
        private readonly AccountRepository $accounts,
        private readonly GameRepository $game,
    ) {
    }

    /**
     * Buys one item. Points are debited atomically (no double spend) and refunded if the
     * delivery fails. Returns an error message, or null on success.
     */
    public function purchase(int $accountId, int $server, int $template): ?string
    {
        $item = $this->shop->findItem($server, $template);
        if ($item === null || !in_array($server, self::DELIVERABLE_SERVERS, true)) {
            return 'Cet objet n\'est pas en vente.';
        }

        $price = (int) $item->price;
        if (!$this->accounts->debitPoints($accountId, $price)) {
            return 'Tu n\'as pas assez de points pour acheter cet objet.';
        }

        try {
            $this->game->addGift($accountId, $template, 1, (bool) $item->jp);
        } catch (PDOException $e) {
            $this->accounts->addPoints($accountId, $price);
            error_log('StarLoco-Web: gift delivery failed for account ' . $accountId . ': ' . $e->getMessage());
            return 'Une erreur s\'est produite, tes points ont été remboursés.';
        }

        $this->shop->logPurchase($accountId, $template, $server);
        return null;
    }
}
