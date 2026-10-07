<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Http\Resources\CharacterCardResource;
use App\Http\Resources\FeedItemResource;
use App\Models\Generation;
use App\Services\Characters\CharacterCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowFeedController extends Controller
{
    /** Сколько работ отдаём за раз. */
    private const PER_PAGE = 36;

    /** Сколько персонажей показываем в ленте. */
    private const CHARACTERS = 60;

    /**
     * Общая лента: картинки и персонажи.
     *
     * Показываем то, что люди согласились показать: закрытые работы
     * и работы безцензурных моделей сюда не попадают. То же правило
     * у персонажей.
     */
    public function __invoke(Request $request, CharacterCatalog $catalog): Response
    {
        if ($request->query('tab') === 'characters') {
            return Inertia::render('Feed/Index', [
                'tab' => 'characters',
                'works' => ['data' => [], 'links' => ['next' => null]],
                'characters' => CharacterCardResource::collection(
                    $catalog->query()->limit(self::CHARACTERS)->get()
                ),
            ]);
        }

        $works = Generation::query()
            ->inFeed()
            ->with('user:id,name')
            ->latest('id')
            ->cursorPaginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Feed/Index', [
            'tab' => 'images',
            'works' => FeedItemResource::collection($works),
            'characters' => [],
        ]);
    }
}
