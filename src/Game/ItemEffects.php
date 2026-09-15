<?php

declare(strict_types=1);

namespace JLoco\Web\Game;

/**
 * Human-readable item effects from website_shop_objects_templates.effects
 * ("effectIdHex#minHex#maxHex#...,..."), e.g. "7d#a#14#0#1d10+9" => "+ 10 à 20 Vitalité".
 */
final class ItemEffects
{
    /** Effect id (hex) => [sign, label]. Unknown ids are skipped. */
    private const array EFFECTS = [
        '99' => ['-', 'Vitalité'],
        '9d' => ['-', 'Terre'],
        '9b' => ['-', 'Feu'],
        '9a' => ['-', 'Air'],
        '98' => ['-', 'Eau'],
        '7d' => ['+', 'Vitalité'],
        '7c' => ['+', 'Sagesse'],
        '76' => ['+', 'Terre'],
        '7e' => ['+', 'Feu'],
        '77' => ['+', 'Air'],
        '7b' => ['+', 'Eau'],
        '6f' => ['+', 'Pa'],
        '65' => ['-', 'Pa'],
        '80' => ['+', 'Pm'],
        '7f' => ['-', 'Pm'],
        '75' => ['+', 'Po'],
        '74' => ['-', 'Po'],
        '70' => ['+', 'Dommage'],
        '91' => ['-', 'Dommage'],
        '8a' => ['+', 'Dommage (%)'],
        'ba' => ['-', 'Dommage (%)'],
        'dc' => ['+', 'Dommage renvoyé'],
        'b2' => ['+', 'Soins'],
        'b3' => ['-', 'Soins'],
        '73' => ['+', 'Coup Critique'],
        '7a' => ['+', 'Echec Critique'],
        'b6' => ['+', 'Invocation'],
        '9e' => ['+', 'Pod'],
        '9f' => ['-', 'Pod'],
        'ae' => ['+', 'Initiative'],
        'af' => ['-', 'Initiative'],
        'b0' => ['+', 'Prospection'],
        'b1' => ['-', 'Prospection'],
        'e1' => ['+', 'Piège'],
        'e2' => ['+', 'Piège (%)'],
        'a0' => ['+', 'Esquive perte Pa (%)'],
        'aa2' => ['-', 'Esquive perte Pa (%)'],
        'a1' => ['+', 'Esquive perte Pm (%)'],
        'a3' => ['-', 'Esquive perte Pm (%)'],
        'f4' => ['+', 'Résistance Neutre'],
        'f9' => ['-', 'Résistance Neutre'],
        'f0' => ['+', 'Résistance Terre'],
        'f5' => ['-', 'Résistance Terre'],
        'f3' => ['+', 'Résistance Feu'],
        'f8' => ['-', 'Résistance Feu'],
        'f2' => ['+', 'Résistance Air'],
        'f7' => ['-', 'Résistance Air'],
        'f1' => ['+', 'Résistance Eau'],
        'f6' => ['-', 'Résistance Eau'],
        'd6' => ['+', 'Résistance Neutre (%)'],
        'db' => ['-', 'Résistance Neutre (%)'],
        'd2' => ['+', 'Résistance Terre (%)'],
        'd7' => ['-', 'Résistance Terre (%)'],
        'd5' => ['+', 'Résistance Feu (%)'],
        'da' => ['-', 'Résistance Feu (%)'],
        'd4' => ['+', 'Résistance Air (%)'],
        'd9' => ['-', 'Résistance Air (%)'],
        'd3' => ['+', 'Résistance Eau (%)'],
        'd8' => ['-', 'Résistance Eau (%)'],
        'fe' => ['+', 'Résistance Neutre face aux combattants (%)'],
        '103' => ['-', 'Résistance Neutre face aux combattants (%)'],
        'fa' => ['+', 'Résistance Terre face aux combattants (%)'],
        'ff' => ['-', 'Résistance Terre face aux combattants (%)'],
        'fd' => ['+', 'Résistance Feu face aux combattants (%)'],
        '102' => ['-', 'Résistance Feu face aux combattants (%)'],
        'fb' => ['+', 'Résistance Eau face aux combattants (%)'],
        '100' => ['-', 'Résistance Eau face aux combattants (%)'],
        'fc' => ['+', 'Résistance Air face aux combattants (%)'],
        '101' => ['-', 'Résistance Air face aux combattants (%)'],
        '108' => ['+', 'Résistance Neutre face aux combattants'],
        '104' => ['+', 'Résistance Terre face aux combattants'],
        '107' => ['+', 'Résistance Feu face aux combattants'],
        '105' => ['+', 'Résistance Eau face aux combattants'],
        '106' => ['+', 'Résistance Air face aux combattants'],
        '8b' => ['+', 'Energie'],
    ];

    /** @return list<string> */
    public static function describe(string $effects): array
    {
        $lines = [];
        foreach (explode(',', $effects) as $effect) {
            $parts = explode('#', $effect);
            if (count($parts) < 3 || !isset(self::EFFECTS[$parts[0]])) {
                continue;
            }
            [$sign, $label] = self::EFFECTS[$parts[0]];
            $min = hexdec($parts[1]);
            $max = hexdec($parts[2]);
            $lines[] = trim($sign . ' ' . $min . ($max > 0 ? ' à ' . $max : '') . ' ' . $label);
        }
        return $lines;
    }
}
