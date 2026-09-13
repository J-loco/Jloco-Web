<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use StarLoco\Web\Config;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Model\Account;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Repository\ServerRepository;

/**
 * Vote rewards: one vote every COOLDOWN seconds per account and per IP.
 */
final readonly class VoteService
{
    public const int COOLDOWN = 3 * 3600;

    public function __construct(
        private AccountRepository $accounts,
        private ServerRepository $servers,
        private Request $request,
        private Config $config,
    ) {
    }

    /** Seconds before this account (and IP) can vote again; 0 when it can vote now. */
    public function secondsUntilNextVote(Account $account, ?int $now = null): int
    {
        $now ??= time();
        $last = max($account->lastVoteAt, $this->servers->lastVoteFromIp($this->ip()));
        return max(0, $last + self::COOLDOWN - $now);
    }

    /** Credits the vote. Returns false if the cooldown is not over (also checked atomically). */
    public function vote(Account $account, ?int $now = null): bool
    {
        $now ??= time();
        if ($this->secondsUntilNextVote($account, $now) > 0) {
            return false;
        }
        if (!$this->accounts->creditVote($account->id, $this->config->votePoints, $now, self::COOLDOWN)) {
            return false;
        }
        $this->servers->recordVoteFromIp($this->ip(), $now);
        return true;
    }

    private function ip(): string
    {
        return $this->request->clientIp($this->config->trustCloudflare);
    }
}
