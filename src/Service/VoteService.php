<?php

declare(strict_types=1);

namespace StarLoco\Web\Service;

use StarLoco\Web\Config;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Repository\ServerRepository;

/**
 * Vote rewards: one vote every COOLDOWN seconds per account and per IP.
 */
final class VoteService
{
    public const COOLDOWN = 3 * 3600;

    public function __construct(
        private readonly AccountRepository $accounts,
        private readonly ServerRepository $servers,
        private readonly Request $request,
        private readonly Config $config,
    ) {
    }

    /** Seconds before this account (and IP) can vote again; 0 when it can vote now. */
    public function secondsUntilNextVote(object $account): int
    {
        $ip = $this->request->clientIp($this->config->trustCloudflare);
        $last = max((int) $account->heurevote, $this->servers->lastVoteFromIp($ip));
        return max(0, $last + self::COOLDOWN - time());
    }

    /** Credits the vote. Returns false if the cooldown is not over (also checked atomically). */
    public function vote(object $account): bool
    {
        if ($this->secondsUntilNextVote($account) > 0) {
            return false;
        }
        $now = time();
        if (!$this->accounts->creditVote((int) $account->guid, $this->config->votePoints, $now, self::COOLDOWN)) {
            return false;
        }
        $this->servers->recordVoteFromIp($this->request->clientIp($this->config->trustCloudflare), $now);
        return true;
    }
}
