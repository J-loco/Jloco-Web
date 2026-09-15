<?php

declare(strict_types=1);

namespace JLoco\Web\Service;

use JLoco\Web\Config;
use JLoco\Web\Model\Account;
use JLoco\Web\Repository\AccountRepository;
use JLoco\Web\Repository\ShopRepository;

/**
 * Buying shop points with Dedipass codes. The Dedipass widget posts "code" and "rate" to the
 * account page; the code is validated against the Dedipass API before crediting.
 */
final readonly class Dedipass
{
    public function __construct(
        private Config $config,
        private AccountRepository $accounts,
        private ShopRepository $shop,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->dedipassPublicKey !== '';
    }

    /** @return array{error: ?string, points: int} */
    public function redeem(Account $account, string $code, string $rate): array
    {
        $code = preg_replace('/[^a-zA-Z0-9]+/', '', $code) ?? '';
        $rate = preg_replace('/[^a-zA-Z0-9\-]+/', '', $rate) ?? '';
        if (!$this->isEnabled() || $code === '' || $rate === '') {
            return ['error' => 'Tu dois saisir un code et choisir un palier.', 'points' => 0];
        }

        $url = 'https://api.dedipass.com/v1/pay/?' . http_build_query(['key' => $this->config->dedipassPublicKey, 'rate' => $rate, 'code' => $code]);
        $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 10]]));
        $result = $response === false ? null : json_decode($response);

        if (!is_object($result) || ($result->status ?? '') !== 'success') {
            return ['error' => 'Le code ' . $code . ' est invalide ou déjà utilisé.', 'points' => 0];
        }

        $points = (int) ($result->virtual_currency ?? 0);
        $this->accounts->addPoints($account->id, $points);

        $rateParts = array_pad(explode('-', (string) ($result->rate ?? '')), 4, '');
        $this->shop->logPointsPurchase($account->name, $points, (string) ($result->code ?? $code), $rateParts[0] . '-' . $rateParts[1], $rateParts[2] . '-' . $rateParts[3]);

        return ['error' => null, 'points' => $points];
    }
}
