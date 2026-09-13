<?php

declare(strict_types=1);

namespace StarLoco\Web\View;

use StarLoco\Web\Http\Response;
use Twig\Environment;

final class View
{
    private ?string $nonce = null;
    private ?string $route = null;

    public function __construct(private readonly Environment $twig)
    {
    }

    /** @param array<string, mixed> $context */
    public function render(string $template, array $context = [], int $status = 200): Response
    {
        $html = $this->twig->render($template, $context + ['current_route' => $this->route, 'csp_nonce' => $this->cspNonce()]);
        return new Response($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function setCurrentRoute(string $route): void
    {
        $this->route = $route;
    }

    /** Per-request nonce for inline scripts allowed by the Content-Security-Policy. */
    public function cspNonce(): string
    {
        return $this->nonce ??= base64_encode(random_bytes(16));
    }
}
