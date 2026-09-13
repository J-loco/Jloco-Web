<?php

declare(strict_types=1);

namespace StarLoco\Web;

use FastRoute\Dispatcher;
use StarLoco\Web\Http\LegacyUrls;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Http\Router;
use StarLoco\Web\Security\Csrf;
use StarLoco\Web\Security\Session;
use StarLoco\Web\Service\AuthService;
use StarLoco\Web\View\View;
use Throwable;

/**
 * Front controller pipeline: legacy redirects → session → remember-me → routing → CSRF → controller.
 */
final class Kernel
{
    public function __construct(private readonly Container $container)
    {
    }

    public function run(): void
    {
        $this->handle($this->container->get(Request::class))->send();
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->dispatch($request);
        } catch (Throwable $e) {
            error_log('StarLoco-Web: ' . $e);
            $response = $this->errorResponse($e);
        }
        return $this->withSecurityHeaders($response);
    }

    private function dispatch(Request $request): Response
    {
        $router = $this->container->get(Router::class);

        if (($legacy = $this->container->get(LegacyUrls::class)->redirectFor($request)) !== null) {
            return Response::redirect($legacy, 301);
        }

        $this->container->get(Session::class)->start();
        $this->container->get(AuthService::class)->restoreFromCookie();

        $match = $router->match($request->method, $request->path);
        $view = $this->container->get(View::class);

        if ($match[0] === Dispatcher::NOT_FOUND) {
            return $view->render('errors/404.html.twig', [], 404);
        }
        if ($match[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $view->render('errors/404.html.twig', [], 405);
        }

        [, $route, $params] = $match;
        $view->setCurrentRoute($route->name);

        if ($request->isPost() && $route->csrf && !$this->container->get(Csrf::class)->isValid($request->input(Csrf::FIELD))) {
            $this->container->get(Session::class)->flash('warning', 'Ta session a expiré, merci de réessayer.');
            $referer = (string) ($request->server['HTTP_REFERER'] ?? '');
            $appUrl = $this->container->get(Config::class)->appUrl;
            return Response::redirect(str_starts_with($referer, $appUrl) ? $referer : $router->url('home'));
        }

        [$class, $method] = $route->handler;
        return $this->container->get($class)->$method($request, $params);
    }

    private function errorResponse(Throwable $e): Response
    {
        $config = $this->container->get(Config::class);
        try {
            return $this->container->get(View::class)->render('errors/500.html.twig', ['error' => $config->debug ? $e : null], 500);
        } catch (Throwable) {
            return new Response($config->debug ? (string) $e : 'Erreur interne. Réessaie plus tard.', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
    }

    private function withSecurityHeaders(Response $response): Response
    {
        $nonce = $this->container->get(View::class)->cspNonce();
        $dedipass = 'https://api.dedipass.com';

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-$nonce' $dedipass",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https:",
                "connect-src 'self' $dedipass",
                "frame-src https://*.dedipass.com",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]));
    }
}
