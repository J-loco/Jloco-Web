<?php

declare(strict_types=1);

namespace StarLoco\Web\Game;

/**
 * Labels for character data stored as ids (world_players.class, sexe, alignement).
 */
final class Character
{
    /** Breed id => [male, female] */
    private const array BREEDS = [
        1 => ['Féca', 'Fécatte'],
        2 => ['Osamodas', 'Osamodas'],
        3 => ['Enutrof', 'Enutrof'],
        4 => ['Sram', 'Sramette'],
        5 => ['Xélor', 'Xélor'],
        6 => ['Ecaflip', 'Ecaflip'],
        7 => ['Eniripsa', 'Eniripsa'],
        8 => ['Iop', 'Iopette'],
        9 => ['Crâ', 'Crâ'],
        10 => ['Sadida', 'Sadida'],
        11 => ['Sacrieur', 'Sacrieuse'],
        12 => ['Pandawa', 'Pandawa'],
    ];

    private const array ALIGNMENTS = [0 => 'Neutre', 1 => 'Bonta', 2 => 'Brâkmar', 3 => 'Mercenaire'];

    public static function breed(int $breed, int $sex): string
    {
        return self::BREEDS[$breed][$sex === 1 ? 1 : 0] ?? 'Inconnu';
    }

    public static function alignment(int $alignment): string
    {
        return self::ALIGNMENTS[$alignment] ?? 'Neutre';
    }
}
