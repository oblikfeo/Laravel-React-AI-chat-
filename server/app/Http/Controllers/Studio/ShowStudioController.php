<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioCatalog;
use App\Services\Studio\StudioLimiter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowStudioController extends Controller
{
    /**
     * Студия: форма создания и лента работ.
     */
    public function __invoke(
        Request $request,
        StudioLimiter $limiter,
        ImageGenerator $generator,
    ): Response {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        $remaining = $limiter->remaining($user, $guest);

        // Работа, принесённая из общей ленты: открываем Студию сразу
        // с ней и на вкладке правки.
        $fromFeed = $request->integer('from_feed')
            ? Generation::query()
                ->inFeed()
                ->whereKey($request->integer('from_feed'))
                ->first()
            : null;

        return Inertia::render('Studio/Index', [
            'fromFeed' => $fromFeed
                ? new GenerationResource($fromFeed)
                : null,
            'generations' => GenerationResource::collection(
                Generation::query()
                    ->ownedBy($user, $guest)
                    ->visible()
                    ->latest('id')
                    ->limit(60)
                    ->get()
            ),

            'models' => StudioCatalog::models($user),
            'aspectRatios' => StudioCatalog::aspectRatios(),
            'styles' => StudioCatalog::styles(),
            'speechModels' => StudioCatalog::speechModels($user),
            'musicModels' => StudioCatalog::musicModels($user),
            'effectModels' => StudioCatalog::effectModels($user),
            'voices' => StudioCatalog::voices(),
            'defaultVoice' => config('studio.default_voice'),
            'defaultMusicModel' => config('studio.music.default_model'),
            'defaultEffectModel' => config('studio.effects.default_model'),
            'maxLyrics' => (int) config('studio.music.max_lyrics'),
            'defaultModel' => config('studio.default_model'),
            'defaultSpeechModel' => config('studio.speech.default_model'),
            'maxVariants' => (int) config('studio.max_variants'),
            'maxSeed' => (int) config('studio.max_seed'),
            'upscaleScales' => config('studio.edit.scales'),
            'maxCombine' => (int) config('studio.edit.max_combine'),

            // Пока провайдер не открыл доступ, интерфейс говорит, что
            // Студия скоро заработает, вместо ошибки на весь экран.
            'studioReady' => $generator->isAvailable(),

            'limit' => [
                'remaining' => $remaining === PHP_INT_MAX ? null : $remaining,
                'total' => $limiter->limitFor($user) === PHP_INT_MAX
                    ? null
                    : $limiter->limitFor($user),
            ],
        ]);
    }
}
