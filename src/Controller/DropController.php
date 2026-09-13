<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Model\Drop;
use StarLoco\Web\Model\DropGroup;
use StarLoco\Web\Model\DropLine;
use StarLoco\Web\Repository\GameRepository;
use StarLoco\Web\Support\Text;

/** Drop viewer: GET /drops?by=monster|item&q=… (shareable URLs). */
final class DropController extends AbstractController
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params): Response
    {
        $by = $request->query('by') === 'item' ? 'item' : 'monster';
        // Game names are utf8mb3: characters outside the BMP (emoji) cannot match anyway.
        $term = trim(Text::withoutSupplementaryCharacters($request->query('q')));

        return $this->render('pages/drops.html.twig', [
            'by' => $by,
            'term' => $term,
            'searched' => $term !== '',
            'groups' => mb_strlen($term) >= 2 ? self::group($this->get(GameRepository::class)->searchDrops($by, $term), $by) : [],
        ]);
    }

    /**
     * Groups drops by monster (listing their items) or by item (listing the monsters).
     *
     * @param list<Drop> $drops
     * @param 'monster'|'item' $by
     * @return list<DropGroup>
     */
    public static function group(array $drops, string $by): array
    {
        $names = [];
        $lines = [];
        foreach ($drops as $drop) {
            $key = $by === 'monster' ? $drop->monsterId : $drop->itemId;
            $names[$key] ??= $by === 'monster' ? $drop->monsterName : $drop->itemName;
            $lines[$key][] = new DropLine($by === 'monster' ? $drop->itemName : $drop->monsterName, $drop->prospecting, $drop->minRate, $drop->maxRate);
        }
        return array_map(static fn (int $key): DropGroup => new DropGroup($names[$key], $lines[$key]), array_keys($names));
    }
}
