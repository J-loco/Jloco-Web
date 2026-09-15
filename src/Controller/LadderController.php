<?php

declare(strict_types=1);

namespace JLoco\Web\Controller;

use JLoco\Web\Http\Request;
use JLoco\Web\Http\Response;
use JLoco\Web\Repository\AccountRepository;
use JLoco\Web\Repository\GameRepository;
use JLoco\Web\Repository\GuildRepository;
use JLoco\Web\Repository\PlayerRepository;
use JLoco\Web\Service\JobLadder;

/** Rankings: one URL per tab, no JavaScript needed. */
final class LadderController extends AbstractController
{
    private const int LIMIT = 50;

    /** @param array<string, string> $params */
    public function pvm(Request $request, array $params): Response
    {
        return $this->tab('pvm', ['players' => $this->get(PlayerRepository::class)->topByXp(self::LIMIT)]);
    }

    /** @param array<string, string> $params */
    public function pvp(Request $request, array $params): Response
    {
        return $this->tab('pvp', ['players' => $this->get(PlayerRepository::class)->topByHonor(self::LIMIT)]);
    }

    /** @param array<string, string> $params */
    public function guilds(Request $request, array $params): Response
    {
        return $this->tab('guilds', ['guilds' => $this->get(GuildRepository::class)->top(self::LIMIT)]);
    }

    /** @param array<string, string> $params */
    public function votes(Request $request, array $params): Response
    {
        return $this->tab('votes', ['voters' => $this->get(AccountRepository::class)->topVoters(self::LIMIT)]);
    }

    /**
     * Job picker; the GET form submits ?job=, redirected to the canonical /ladder/jobs/{job}.
     *
     * @param array<string, string> $params
     */
    public function jobs(Request $request, array $params): Response
    {
        if (ctype_digit($request->query('job'))) {
            return Response::redirect($this->url('ladder_job', ['job' => $request->query('job')]), 302);
        }
        return $this->tab('jobs', ['jobs' => $this->get(GameRepository::class)->jobs(), 'job' => null, 'players' => []]);
    }

    /** @param array<string, string> $params */
    public function job(Request $request, array $params): Response
    {
        $jobs = $this->get(GameRepository::class)->jobs();
        $jobId = (int) $params['job'];
        if (!isset($jobs[$jobId])) {
            return $this->notFound();
        }
        return $this->tab('jobs', ['jobs' => $jobs, 'job' => $jobId, 'players' => $this->get(JobLadder::class)->ranking($jobId, $jobs, self::LIMIT)]);
    }

    /** @param array<string, mixed> $context */
    private function tab(string $tab, array $context): Response
    {
        return $this->render('pages/ladder.html.twig', ['tab' => $tab] + $context);
    }
}
