<?php

declare(strict_types=1);

namespace StarLoco\Web\View;

use DateTimeImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use StarLoco\Web\Config;
use StarLoco\Web\Container;
use StarLoco\Web\Game\Character;
use StarLoco\Web\Game\Experience;
use StarLoco\Web\Game\ItemEffects;
use StarLoco\Web\Http\Router;
use StarLoco\Web\Security\Csrf;
use StarLoco\Web\Security\Session;
use StarLoco\Web\Service\AuthService;
use StarLoco\Web\Service\Sidebar;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Template helpers. Services are fetched lazily so that rendering an error page does not need the database.
 */
final class AppExtension extends AbstractExtension
{
    public function __construct(private readonly Container $container)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('url', fn (string $name, array $params = []) => $this->container->get(Router::class)->url($name, $params)),
            new TwigFunction('asset', $this->asset(...)),
            new TwigFunction('csrf_field', fn () => new Markup('<input type="hidden" name="' . Csrf::FIELD . '" value="' . $this->container->get(Csrf::class)->token() . '">', 'UTF-8')),
            new TwigFunction('current_account', fn () => $this->container->get(AuthService::class)->account()),
            new TwigFunction('is_admin', fn () => $this->container->get(AuthService::class)->isAdmin()),
            new TwigFunction('flashes', fn () => $this->container->get(Session::class)->takeFlashes()),
            new TwigFunction('sidebar', fn () => $this->container->get(Sidebar::class)->data()),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('breed', fn (int $breed, int $sex = 0) => Character::breed($breed, $sex)),
            new TwigFilter('alignment', fn (int $alignment) => Character::alignment($alignment)),
            new TwigFilter('xp_progress', fn (int $xp, int $level) => Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, $level, $xp)),
            new TwigFilter('item_effects', fn (?string $effects) => ItemEffects::describe((string) $effects)),
            new TwigFilter('date_fr', $this->formatDate(...)),
            new TwigFilter('legacy_date', $this->legacyDate(...)),
            new TwigFilter('duration', $this->duration(...)),
        ];
    }

    /** URL of a file in public/, with its modification time for cache busting. */
    private function asset(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');
        $version = is_file($file) ? '?v=' . filemtime($file) : '';
        return $this->container->get(Config::class)->appUrl . ltrim($path, '/') . $version;
    }

    /** @param 'long'|'short'|'datetime' $format */
    private function formatDate(DateTimeInterface|string|null $date, string $format = 'long'): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        if (is_string($date)) {
            $date = new DateTimeImmutable($date);
        }
        $pattern = match ($format) {
            'short' => 'dd/MM/yyyy',
            'datetime' => "d MMMM yyyy 'à' HH'h'mm",
            default => 'd MMMM yyyy',
        };
        return (string) (new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, $pattern))->format($date);
    }

    /** world_accounts.lastConnectionDate is stored as "YYYY~MM~DD~HH~MM". */
    private function legacyDate(?string $value): ?DateTimeImmutable
    {
        $parts = explode('~', (string) $value);
        if (count($parts) < 5) {
            return null;
        }
        return DateTimeImmutable::createFromFormat('Y-n-j G:i', sprintf('%d-%d-%d %d:%02d', ...array_map('intval', array_slice($parts, 0, 5)))) ?: null;
    }

    /** "2 h 15 min", "45 min", "3 h" (rounded up to the minute). */
    private function duration(int $seconds): string
    {
        $totalMinutes = (int) ceil(max(0, $seconds) / 60);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return trim(($hours > 0 ? $hours . ' h ' : '') . ($minutes > 0 || $hours === 0 ? $minutes . ' min' : ''));
    }
}
