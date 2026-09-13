<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Config;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Service\VoteService;

final class VoteController extends AbstractController
{
    public function show(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        return $this->render('pages/vote.html.twig', [
            'wait' => $account !== null ? $this->get(VoteService::class)->secondsUntilNextVote($account) : 0,
        ]);
    }

    /** Credits the vote then sends the player to the voting site. */
    public function vote(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $votes = $this->get(VoteService::class);
        $account = $this->auth()->account();

        if (!$votes->vote($account)) {
            $this->flash('warning', 'Tu as déjà voté récemment, reviens un peu plus tard.');
            return $this->redirectTo('vote');
        }
        return Response::redirect($this->get(Config::class)->voteUrl);
    }
}
