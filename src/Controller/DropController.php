<?php

declare(strict_types=1);

namespace StarLoco\Web\Controller;

use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Response;
use StarLoco\Web\Repository\GameRepository;
use StarLoco\Web\Support\Text;

/** Drop viewer: GET /drops?by=monster|item&q=… (shareable URLs). */
final class DropController extends AbstractController
{
    public function index(Request $request, array $params): Response
    {
        $by = $request->query('by') === 'item' ? 'item' : 'monster';
        // Game names are utf8mb3: characters outside the BMP (emoji) cannot match anyway.
        $term = trim(Text::withoutSupplementaryCharacters($request->query('q')));

        $groups = [];
        if (mb_strlen($term) >= 2) {
            foreach ($this->get(GameRepository::class)->searchDrops($by, $term) as $drop) {
                $key = $by === 'monster' ? $drop->monster_id : $drop->item_id;
                $groups[$key] ??= (object) ['name' => $by === 'monster' ? $drop->monster_name : $drop->item_name, 'drops' => []];
                $groups[$key]->drops[] = (object) [
                    'name' => $by === 'monster' ? $drop->item_name : $drop->monster_name,
                    'prospecting' => (int) $drop->ceil,
                    'min' => (float) $drop->percentGrade1,
                    'max' => (float) $drop->percentGrade5,
                ];
            }
        }

        return $this->render('pages/drops.html.twig', [
            'by' => $by,
            'term' => $term,
            'searched' => $term !== '',
            'groups' => array_values($groups),
        ]);
    }
}
