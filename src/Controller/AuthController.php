<?php

declare(strict_types=1);

namespace JLoco\Web\Controller;

use JLoco\Web\Captcha;
use JLoco\Web\Config;
use JLoco\Web\Http\Request;
use JLoco\Web\Http\Response;
use JLoco\Web\Repository\AccountRepository;
use JLoco\Web\Security\Session;
use JLoco\Web\Security\Throttle;
use JLoco\Web\Service\PasswordResetService;

final class AuthController extends AbstractController
{
    private const string RESET_ACCOUNT = 'password_reset_account';

    /** @param array<string, string> $params */
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

    /** @param array<string, string> $params */
    public function logout(Request $request, array $params): Response
    {
        $this->auth()->logout();
        $this->flash('info', 'Tu es déconnecté.');
        return $this->redirectTo('home');
    }

    /** @param array<string, string> $params */
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
            $values = ['username' => $input['username'], 'email' => $input['email'], 'question' => $input['question']];
        }

        return $this->render('pages/register.html.twig', ['values' => $values, 'errors' => $errors], $errors === [] ? 200 : 422);
    }

    /**
     * Forgotten password. With outgoing mail: a reset link is emailed. Without: step 1 account name,
     * step 2 secret answer (the account is kept in the session).
     *
     * @param array<string, string> $params
     */
    public function passwordReset(Request $request, array $params): Response
    {
        if ($this->get(Config::class)->mailEnabled()) {
            return $this->passwordResetByEmail($request);
        }

        $session = $this->get(Session::class);
        $accounts = $this->get(AccountRepository::class);

        if ($request->isPost() && $request->has('account')) {
            $account = $accounts->findByName(trim($request->input('account')));
            if ($account === null) {
                return $this->render('pages/password_reset.html.twig', ['step' => 'account', 'error' => 'Ce nom de compte n\'existe pas.'], 422);
            }
            $session->set(self::RESET_ACCOUNT, $account->name);
            return $this->redirectTo('password_reset');
        }

        if ($request->query('restart') === '1') {
            $session->remove(self::RESET_ACCOUNT);
            return $this->redirectTo('password_reset');
        }

        $name = $session->get(self::RESET_ACCOUNT);
        $account = is_string($name) ? $accounts->findByName($name) : null;
        if ($account === null) {
            return $this->render('pages/password_reset.html.twig', ['step' => 'account', 'error' => null]);
        }

        if ($request->isPost() && $request->has('answer')) {
            $result = $this->auth()->resetPasswordWithAnswer($account->name, $request->input('answer'));
            if ($result['error'] === null) {
                $session->remove(self::RESET_ACCOUNT);
                return $this->render('pages/password_reset.html.twig', ['step' => 'done', 'password' => $result['password'], 'error' => null]);
            }
            return $this->render('pages/password_reset.html.twig', ['step' => 'answer', 'question' => $account->question, 'error' => $result['error']], 422);
        }

        return $this->render('pages/password_reset.html.twig', ['step' => 'answer', 'question' => $account->question, 'error' => null]);
    }

    /**
     * Page of the emailed link: choose a new password.
     *
     * @param array<string, string> $params
     */
    public function passwordResetConfirm(Request $request, array $params): Response
    {
        $resets = $this->get(PasswordResetService::class);
        if ($resets->accountForLink($params['selector'], $params['token']) === null) {
            $this->flash('danger', 'Ce lien de réinitialisation n\'est plus valide. Fais une nouvelle demande.');
            return $this->redirectTo('password_reset');
        }

        if ($request->isPost()) {
            $error = $resets->reset($params['selector'], $params['token'], $request->input('password'), $request->input('password_confirm'));
            if ($error === null) {
                $this->flash('success', 'Ton mot de passe a été changé, tu peux te connecter.');
                return $this->redirectTo('login');
            }
            return $this->render('pages/password_reset_confirm.html.twig', ['error' => $error, 'params' => $params], 422);
        }
        return $this->render('pages/password_reset_confirm.html.twig', ['error' => null, 'params' => $params]);
    }

    /** @param array<string, string> $params */
    public function captcha(Request $request, array $params): Response
    {
        return new Response($this->get(Captcha::class)->generate(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function passwordResetByEmail(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->render('pages/password_reset.html.twig', ['step' => 'email', 'error' => null]);
        }
        if (!$this->get(PasswordResetService::class)->request(trim($request->input('account')))) {
            return $this->render('pages/password_reset.html.twig', ['step' => 'email', 'error' => Throttle::message()], 429);
        }
        return $this->render('pages/password_reset.html.twig', ['step' => 'email_sent', 'error' => null]);
    }
}
