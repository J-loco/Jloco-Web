<?php

declare(strict_types=1);

namespace JLoco\Web\Service;

use JLoco\Web\Game\Experience;
use JLoco\Web\Model\JobRanking;
use JLoco\Web\Repository\PlayerRepository;

/** Best characters of a job, ranked by job level then experience. */
final readonly class JobLadder
{
    public function __construct(private PlayerRepository $players)
    {
    }

    /**
     * @param array<int, string> $jobNames id => name of every job
     * @return list<JobRanking>
     */
    public function ranking(int $jobId, array $jobNames, int $limit = 50): array
    {
        return self::rank($this->players->jobsOfCharactersWithJob($jobId), $jobId, $jobNames, $limit);
    }

    /**
     * @param array<string, array<int, int>> $characters name => [jobId => xp]
     * @param array<int, string> $jobNames
     * @return list<JobRanking>
     */
    public static function rank(array $characters, int $jobId, array $jobNames, int $limit): array
    {
        $ranking = [];
        foreach ($characters as $name => $jobs) {
            if (!isset($jobs[$jobId])) {
                continue;
            }
            $xp = $jobs[$jobId];
            $level = Experience::levelFromXp(Experience::JOB, Experience::MAX_JOB_LEVEL, $xp);
            $otherJobs = [];
            foreach (array_keys($jobs) as $id) {
                if ($id !== $jobId && isset($jobNames[$id])) {
                    $otherJobs[] = $jobNames[$id];
                }
            }
            $ranking[] = new JobRanking((string) $name, $level, $xp, Experience::progress(Experience::JOB, Experience::MAX_JOB_LEVEL, $level, $xp), $otherJobs);
        }
        usort($ranking, static fn (JobRanking $a, JobRanking $b): int => [$b->level, $b->xp] <=> [$a->level, $a->xp]);
        return array_slice($ranking, 0, $limit);
    }
}
