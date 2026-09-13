<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Container;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Http\Router;
use StarLoco\Web\Security\Session;
use StarLoco\Web\Service\AuthService;
use StarLoco\Web\View\View;

/**
 * Helpers shared by controllers. Actions receive (Request $request, array<string, string> $params)
 * and return a Response.
 */
abstract class AbstractController
{
    public function __construct(protected readonly Container $container)
    {
    }

    /** @param array<string, mixed> $context */
    protected function render(string $template, array $context = [], int $status = 200): Response
    {
        return $this->container->get(View::class)->render($template, $context, $status);
    }

    /** @param array<string, scalar> $params */
    protected function redirectTo(string $route, array $params = []): Response
    {
        return Response::redirect($this->url($route, $params));
    }

    /** @param array<string, scalar> $params */
    protected function url(string $route, array $params = []): string
    {
        return $this->container->get(Router::class)->url($route, $params);
    }

    /** @param 'success'|'info'|'warning'|'danger' $type */
    protected function flash(string $type, string $message): void
    {
        $this->container->get(Session::class)->flash($type, $message);
    }

    protected function auth(): AuthService
    {
        return $this->container->get(AuthService::class);
    }

    /** For pages that need a logged-in account: controllers call it when auth()->account() is null. */
    protected function loginRedirect(): Response
    {
        $this->flash('info', 'Connecte-toi pour accéder à cette page.');
        return $this->redirectTo('login');
    }

    protected function notFound(): Response
    {
        return $this->render('errors/404.html.twig', [], 404);
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    protected function get(string $id): object
    {
        return $this->container->get($id);
    }
}
