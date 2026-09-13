<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** What authentication needs from world_accounts. Kept apart from Account so the hash never reaches templates. */
final readonly class Credentials
{
    public function __construct(
        public int $accountId,
        public string $passwordHash,
    ) {
    }
}
