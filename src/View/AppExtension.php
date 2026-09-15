<?php

declare(strict_types=1);

namespace JLoco\Web\View;

use DateTimeImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use JLoco\Web\Config;
use JLoco\Web\Container;
use JLoco\Web\Game\Character as CharacterLabels;
use JLoco\Web\Game\Experience;
use JLoco\Web\Game\ItemEffects;
use JLoco\Web\Http\Router;
use JLoco\Web\Model\Character;
use JLoco\Web\Model\ShopItem;
use JLoco\Web\Security\Csrf;
use JLoco\Web\Security\Session;
use JLoco\Web\Service\AuthService;
use JLoco\Web\Service\Sidebar;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Template helpers. Services are fetched lazily so that rendering an error page does not need the database.
 */
final class AppExtension extends AbstractExtension
{
    private const string PUBLIC_DIR = __DIR__ . '/../../public/';

    public function __construct(private readonly Container $container)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('url', fn (string $name, array $params = []): string => $this->container->get(Router::class)->url($name, $params)),
            new TwigFunction('asset', $this->asset(...)),
            new TwigFunction('csrf_field', fn (): Markup => new Markup('<input type="hidden" name="' . Csrf::FIELD . '" value="' . $this->container->get(Csrf::class)->token() . '">', 'UTF-8')),
            new TwigFunction('current_account', fn () => $this->container->get(AuthService::class)->account()),
            new TwigFunction('is_admin', fn (): bool => $this->container->get(AuthService::class)->isAdmin()),
            new TwigFunction('flashes', fn (): array => $this->container->get(Session::class)->takeFlashes()),
            new TwigFunction('sidebar', fn () => $this->container->get(Sidebar::class)->data()),
            new TwigFunction('item_image', $this->itemImage(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('breed', static fn (Character $character): string => CharacterLabels::breed($character->breed, $character->sex)),
            new TwigFilter('alignment', static fn (int $alignment): string => CharacterLabels::alignment($alignment)),
            new TwigFilter('xp_progress', static fn (Character $character): int => Experience::progress(Experience::PLAYER, Experience::MAX_PLAYER_LEVEL, $character->level, $character->xp)),
            new TwigFilter('item_effects', static fn (ShopItem $item): array => ItemEffects::describe($item->effects)),
            new TwigFilter('date_fr', self::formatDate(...)),
            new TwigFilter('duration', self::duration(...)),
        ];
    }

    /** URL of a file in public/, with its modification time for cache busting. */
    private function asset(string $path): string
    {
        $file = self::PUBLIC_DIR . ltrim($path, '/');
        $version = is_file($file) ? '?v=' . filemtime($file) : '';
        return $this->container->get(Config::class)->appUrl . ltrim($path, '/') . $version;
    }

    /** Sprite exported by bin/export-item-images, or null when it does not exist. */
    private function itemImage(ShopItem $item): ?string
    {
        $path = sprintf('assets/img/items/%d/%d.png', $item->type, $item->skin);
        return $item->skin > 0 && is_file(self::PUBLIC_DIR . $path) ? $this->asset($path) : null;
    }

    /** @param 'long'|'short'|'datetime' $format */
    public static function formatDate(DateTimeInterface|string|null $date, string $format = 'long'): string
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
        return (string) new IntlDateFormatter('fr_FR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, $pattern)->format($date);
    }

    /** "2 h 15 min", "45 min", "3 h" (rounded up to the minute). */
    public static function duration(int $seconds): string
    {
        $totalMinutes = (int) ceil(max(0, $seconds) / 60);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return trim(($hours > 0 ? $hours . ' h ' : '') . ($minutes > 0 || $hours === 0 ? $minutes . ' min' : ''));
    }
}
