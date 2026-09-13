<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Repository\PlayerRepository;
use StarLoco\Web\Service\Dedipass;

final class AccountController extends AbstractController
{
    public function show(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $account = $this->auth()->account();

        return $this->render('pages/account.html.twig', [
            'account' => $account,
            'characters' => $this->get(PlayerRepository::class)->byAccount((int) $account->guid),
            'dedipass' => $this->get(Dedipass::class)->isEnabled(),
        ]);
    }

    public function privacy(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $this->get(AccountRepository::class)->togglePrivacy((int) $this->auth()->accountId(), $params['flag']);
        $this->flash('success', 'Préférence enregistrée.');
        return $this->redirectTo('account');
    }

    public function password(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $error = $this->auth()->changePassword(
            (int) $this->auth()->accountId(),
            $request->input('answer'),
            $request->input('password'),
            $request->input('password_confirm'),
        );
        $error === null
            ? $this->flash('success', 'Ton mot de passe a été changé.')
            : $this->flash('danger', $error);
        return Response::redirect($this->url('account') . '#password');
    }

    public function dedipass(Request $request, array $params): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        $result = $this->get(Dedipass::class)->redeem($this->auth()->account(), $request->input('code'), $request->input('rate'));
        $result['error'] === null
            ? $this->flash('success', 'Tu as été crédité de ' . $result['points'] . ' points.')
            : $this->flash('danger', $result['error']);
        return Response::redirect($this->url('account') . '#points');
    }
}
