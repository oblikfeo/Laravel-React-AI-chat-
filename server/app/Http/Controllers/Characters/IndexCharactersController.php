<?php

namespace App\Http\Controllers\Characters;

use App\Http\Controllers\Controller;
use App\Http\Resources\CharacterCardResource;
use App\Http\Resources\CharacterEditResource;
use App\Http\Resources\GenerationResource;
use App\Models\Character;
use App\Models\Generation;
use App\Services\Characters\CharacterCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexCharactersController extends Controller
{
    /** Сколько персонажей каталога показываем. */
    private const CATALOG_LIMIT = 60;

    /**
     * Раздел персонажей: общий каталог и свои.
     */
    public function __invoke(Request $request, CharacterCatalog $catalog): Response
    {
        $user = $request->user();

        // Вкладка «свои» есть только у вошедшего.
        $tab = $user && $request->query('tab') === 'mine' ? 'mine' : 'explore';

        $search = $request->string('q')->trim()->toString() ?: null;
        $tag = $request->string('tag')->trim()->toString() ?: null;

        return Inertia::render('Characters/Index', [
            'tab' => $tab,
            'filters' => ['q' => $search, 'tag' => $tag],

            'catalog' => CharacterCardResource::collection(
                $catalog->query($search, $tag)->limit(self::CATALOG_LIMIT)->get()
            ),
            'popularTags' => $catalog->popularTags(),

            // Свои персонажи отдаются целиком: автор их правит.
            'mine' => $user
                ? CharacterEditResource::collection($user->characters()->get())
                : [],

            'form' => [
                'limits' => config('characters.limits'),
                'suggestedTags' => config('characters.suggested_tags'),
                'defaultModel' => config('characters.default_model'),
                // Персонажи на этих моделях в каталог не попадают:
                // форма скажет об этом до сохранения.
                'privateModels' => config('characters.public_uncensored')
                    ? []
                    : Character::privateModels(),
                'canCreateMore' => $user
                    ? $user->characters()->count() < config('characters.max_per_user')
                    : false,
            ],

            // Свои картинки из Студии: из них можно взять аватар.
            'studioWorks' => $user
                ? GenerationResource::collection(
                    Generation::query()
                        ->where('user_id', $user->id)
                        ->where('kind', Generation::KIND_IMAGE)
                        ->where('status', Generation::STATUS_READY)
                        ->whereNotNull('path')
                        ->latest('id')
                        ->limit(40)
                        ->get()
                )
                : [],
        ]);
    }
}
