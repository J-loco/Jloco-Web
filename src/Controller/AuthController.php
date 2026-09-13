<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Captcha;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Repository\AccountRepository;
use StarLoco\Web\Security\Session;

final class AuthController extends AbstractController
{
    private const RESET_ACCOUNT = 'password_reset_account';

    public function login(Request $request, array $params): Response
    {
        if ($this->auth()->account() !== null) {
            return $this->redirectTo('account');
        }

        if ($request->isPost()) {
            $error = $this->auth()->attempt($request->input('username'), $request->input('password'), $request->has('remember'));
            if ($error === null) {
                $this->flash('success', 'Connexion réussie, bon jeu !');
                return $this->redirectTo('home');
            }
            return $this->render('pages/login.html.twig', ['error' => $error, 'username' => $request->input('username')], 422);
        }

        return $this->render('pages/login.html.twig', ['error' => null, 'username' => '']);
    }

    public function logout(Request $request, array $params): Response
    {
        $this->auth()->logout();
        $this->flash('info', 'Tu es déconnecté.');
        return $this->redirectTo('home');
    }

    public function register(Request $request, array $params): Response
    {
        if ($this->auth()->account() !== null) {
            return $this->redirectTo('account');
        }

        $values = ['username' => '', 'email' => '', 'question' => ''];
        $errors = [];

        if ($request->isPost()) {
            $input = [
                'username' => trim($request->input('username')),
                'email' => trim($request->input('email')),
                'password' => $request->input('password'),
                'password_confirm' => $request->input('password_confirm'),
                'question' => $request->input('question'),
                'answer' => $request->input('answer'),
                'captcha' => $request->input('captcha'),
                'terms' => $request->has('terms'),
            ];
            $errors = $this->auth()->register($input);
            if ($errors === []) {
                $this->flash('success', 'Ton compte a été créé, tu peux maintenant te connecter.');
                return $this->redirectTo('login');
            }
            $values = array_intersect_key($input, $values);
        }

        return $this->render('pages/register.html.twig', ['values' => $values, 'errors' => $errors], $errors === [] ? 200 : 422);
    }

    /** Step 1: account name. Step 2: secret answer (the account is kept in the session). */
    public function passwordReset(Request $request, array $params): Response
    {
        $session = $this->get(Session::class);
        $accounts = $this->get(AccountRepository::class);

        if ($request->isPost() && $request->has('account')) {
            $account = $accounts->findByName(trim($request->input('account')));
            if ($account === null) {
                return $this->render('pages/password_reset.html.twig', ['step' => 'account', 'error' => 'Ce nom de compte n\'existe pas.'], 422);
            }
            $session->set(self::RESET_ACCOUNT, $account->account);
            return $this->redirectTo('password_reset');
        }

        $name = $session->get(self::RESET_ACCOUNT);
        $account = is_string($name) ? $accounts->findByName($name) : null;
        if ($account === null) {
            return $this->render('pages/password_reset.html.twig', ['step' => 'account', 'error' => null]);
        }

        if ($request->isPost() && $request->has('answer')) {
            $result = $this->auth()->resetPassword($account->account, $request->input('answer'));
            if ($result['error'] === null) {
                $session->remove(self::RESET_ACCOUNT);
                return $this->render('pages/password_reset.html.twig', ['step' => 'done', 'password' => $result['password'], 'error' => null]);
            }
            return $this->render('pages/password_reset.html.twig', ['step' => 'answer', 'account' => $account, 'error' => $result['error']], 422);
        }

        if ($request->query('restart') === '1') {
            $session->remove(self::RESET_ACCOUNT);
            return $this->redirectTo('password_reset');
        }

        return $this->render('pages/password_reset.html.twig', ['step' => 'answer', 'account' => $account, 'error' => null]);
    }

    public function captcha(Request $request, array $params): Response
    {
        return new Response($this->get(Captcha::class)->generate(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store',
        ]);
    }
}
