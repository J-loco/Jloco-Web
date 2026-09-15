<?php

declare(strict_types=1);

namespace JLoco\Web\Controller;

use JLoco\Web\Config;
use JLoco\Web\Http\Request;
use JLoco\Web\Http\Response;
use JLoco\Web\Service\VoteService;

final class VoteController extends AbstractController
{
    /** @param array<string, string> $params */
    public function show(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        return $this->render('pages/vote.html.twig', [
            'wait' => $account !== null ? $this->get(VoteService::class)->secondsUntilNextVote($account) : 0,
        ]);
    }

    /**
     * Credits the vote then sends the player to the voting site.
     *
     * @param array<string, string> $params
     */
    public function vote(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        if ($account === null) {
            return $this->loginRedirect();
        }
        if (!$this->get(VoteService::class)->vote($account)) {
            $this->flash('warning', 'Tu as déjà voté récemment, reviens un peu plus tard.');
            return $this->redirectTo('vote');
        }
        return Response::redirect($this->get(Config::class)->voteUrl);
    }
}
