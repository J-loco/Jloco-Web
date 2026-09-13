<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Game\Experience;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Repository\GameRepository;
use StarLoco\Web\Repository\GuildRepository;
use StarLoco\Web\Repository\PlayerRepository;

/** Rankings: one URL per tab, no JavaScript needed. */
final class LadderController extends AbstractController
{
    private const LIMIT = 50;

    public function pvm(Request $request, array $params): Response
    {
        return $this->tab('pvm', ['players' => $this->get(PlayerRepository::class)->topByXp(self::LIMIT)]);
    }

    public function pvp(Request $request, array $params): Response
    {
        return $this->tab('pvp', ['players' => $this->get(PlayerRepository::class)->topByHonor(self::LIMIT)]);
    }

    public function guilds(Request $request, array $params): Response
    {
        return $this->tab('guilds', ['guilds' => $this->get(GuildRepository::class)->top(self::LIMIT)]);
    }

    public function votes(Request $request, array $params): Response
    {
        return $this->tab('votes', ['voters' => $this->get(AccountRepository::class)->topVoters(self::LIMIT)]);
    }

    /** Job picker; the GET form submits ?job=, redirected to the canonical /ladder/jobs/{job}. */
    public function jobs(Request $request, array $params): Response
    {
        if (ctype_digit($request->query('job'))) {
            return Response::redirect($this->url('ladder_job', ['job' => $request->query('job')]), 302);
        }
        return $this->tab('jobs', ['jobs' => $this->get(GameRepository::class)->jobs(), 'job' => null, 'players' => []]);
    }

    public function job(Request $request, array $params): Response
    {
        $jobs = $this->get(GameRepository::class)->jobs();
        $jobId = (int) $params['job'];
        if (!isset($jobs[$jobId])) {
            return $this->notFound();
        }

        $players = [];
        foreach ($this->get(PlayerRepository::class)->withJob($jobId) as $player) {
            $xp = 0;
            $otherJobs = [];
            foreach (explode(';', (string) $player->jobs) as $entry) {
                [$id, $jobXp] = array_pad(explode(',', $entry), 2, '0');
                if ((int) $id === $jobId) {
                    $xp = (int) $jobXp;
                } elseif (isset($jobs[(int) $id])) {
                    $otherJobs[] = $jobs[(int) $id];
                }
            }
            $level = Experience::levelFromXp(Experience::JOB, Experience::MAX_JOB_LEVEL, $xp);
            $players[] = (object) [
                'name' => $player->name,
                'level' => $level,
                'xp' => $xp,
                'progress' => Experience::progress(Experience::JOB, Experience::MAX_JOB_LEVEL, $level, $xp),
                'otherJobs' => $otherJobs,
            ];
        }
        usort($players, fn (object $a, object $b) => [$b->level, $b->xp] <=> [$a->level, $a->xp]);

        return $this->tab('jobs', ['jobs' => $jobs, 'job' => $jobId, 'players' => array_slice($players, 0, self::LIMIT)]);
    }

    /** @param array<string, mixed> $context */
    private function tab(string $tab, array $context): Response
    {
        return $this->render('pages/ladder.html.twig', ['tab' => $tab] + $context);
    }
}
