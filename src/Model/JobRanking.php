<?php

declare(strict_types=1);

namespace StarLoco\Web\Model;

/** A character in a job ranking. */
final readonly class JobRanking
{
    public function __construct(
        public string $name,
        public int $level,
        public int $xp,
        public int $progress,
        /** @var list<string> */
        public array $otherJobs,
    ) {
    }
}
