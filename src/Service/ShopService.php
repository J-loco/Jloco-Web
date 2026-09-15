<?php

declare(strict_types=1);

namespace JLoco\Web\Service;

use PDOException;
use JLoco\Web\Config;
use JLoco\Web\Repository\AccountRepository;
use JLoco\Web\Repository\GameRepository;
use JLoco\Web\Repository\ShopRepository;

final readonly class ShopService
{
    /**
     * Legacy shop-only IDs are absent from the 1.29 client language files. Deliver equivalent
     * authentic client items; migration 006 attaches the original reward actions to these IDs.
     *
     * @var array<int, int>
     */
    private const array DELIVERY_TEMPLATES = [
        15009 => 12018,
        15010 => 12023,
        15011 => 12024,
        15012 => 12025,
        15013 => 9964,
        15014 => 12010,
        15015 => 12011,
        15016 => 12012,
        15017 => 12013,
        15018 => 12014,
        15019 => 12015,
        26001 => 12017,
        26002 => 12019,
        26003 => 8340,
        26004 => 12022,
        26005 => 12839,
        26006 => 12777,
        26007 => 10912,
        26008 => 10913,
        26009 => 10914,
        26010 => 8337,
        26011 => 8339,
        26012 => 10910,
    ];

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

        $gameDatabase = $this->config->shopServers[$server];
        $deliveryTemplate = self::DELIVERY_TEMPLATES[$template] ?? $template;
        try {
            if (!$this->game->hasItemTemplate($gameDatabase, $deliveryTemplate)) {
                error_log("JLoco-Web: delivery template $deliveryTemplate for shop template $template is missing from game database $gameDatabase");
                return 'Cet objet est temporairement indisponible.';
            }
        } catch (PDOException $e) {
            error_log('JLoco-Web: shop availability check failed: ' . $e->getMessage());
            return 'La boutique est temporairement indisponible.';
        }

        if (!$this->accounts->debitPoints($accountId, $item->price)) {
            return 'Tu n\'as pas assez de points pour acheter cet objet.';
        }

        try {
            $this->game->addGift($gameDatabase, $accountId, $deliveryTemplate, 1, $item->maxStats);
        } catch (PDOException $e) {
            $this->accounts->addPoints($accountId, $item->price);
            error_log('JLoco-Web: gift delivery failed for account ' . $accountId . ': ' . $e->getMessage());
            return 'Une erreur s\'est produite, tes points ont été remboursés.';
        }

        $this->shop->logPurchase($accountId, $template, $server);
        return null;
    }
}
