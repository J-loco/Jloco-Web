<?php

declare(strict_types=1);

namespace JLoco\Web\Controller;

use JLoco\Web\Http\Request;
use JLoco\Web\Http\Response;
use JLoco\Web\Repository\AccountRepository;
use JLoco\Web\Repository\PlayerRepository;
use JLoco\Web\Service\Dedipass;

final class AccountController extends AbstractController
{
    /** @param array<string, string> $params */
    public function show(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        if ($account === null) {
            return $this->loginRedirect();
        }

        return $this->render('pages/account.html.twig', [
            'account' => $account,
            'characters' => $this->get(PlayerRepository::class)->byAccount($account->id),
            'dedipass' => $this->get(Dedipass::class)->isEnabled(),
        ]);
    }

    /** @param array<string, string> $params */
    public function privacy(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        if ($account === null) {
            return $this->loginRedirect();
        }
        $flag = $params['flag'];
        if (!array_key_exists($flag, AccountRepository::PRIVACY_FLAGS)) {
            return $this->notFound();
        }
        $this->get(AccountRepository::class)->togglePrivacy($account->id, $flag);
        $this->flash('success', 'Préférence enregistrée.');
        return $this->redirectTo('account');
    }

    /** @param array<string, string> $params */
    public function password(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        if ($account === null) {
            return $this->loginRedirect();
        }
        $error = $this->auth()->changePassword($account->id, $request->input('answer'), $request->input('password'), $request->input('password_confirm'));
        if ($error === null) {
            $this->flash('success', 'Ton mot de passe a été changé.');
        } else {
            $this->flash('danger', $error);
        }
        return Response::redirect($this->url('account') . '#password');
    }

    /** @param array<string, string> $params */
    public function dedipass(Request $request, array $params): Response
    {
        $account = $this->auth()->account();
        if ($account === null) {
            return $this->loginRedirect();
        }
        $result = $this->get(Dedipass::class)->redeem($account, $request->input('code'), $request->input('rate'));
        if ($result['error'] === null) {
            $this->flash('success', 'Tu as été crédité de ' . $result['points'] . ' points.');
        } else {
            $this->flash('danger', $result['error']);
        }
        return Response::redirect($this->url('account') . '#points');
    }
}
