<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioCatalog;
use App\Services\Studio\StudioCompose;
use App\Services\Studio\StudioLimiter;
use App\Services\Studio\VideoLimiter;
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
        VideoLimiter $videoLimiter,
        ImageGenerator $generator,
        StudioCompose $compose,
    ): Response {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        $remaining = $limiter->remaining($user, $guest);

        return Inertia::render('Studio/Index', [
            // С чем открыть Студию, если пришли из чата или ленты.
            'compose' => $compose->fromRequest($request, $user, $guest),
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
            'videoModels' => StudioCatalog::videoModels(),
            'defaultVideoModel' => config('studio.video.default_model'),
            'editPrices' => StudioCatalog::editPrices(),

            // Видео — только на платном тарифе и со своим лимитом.
            // Гостю и бесплатному тарифу показываем, что оно есть и
            // как его получить, а не прячем вкладку.
            'video' => [
                'access' => match (true) {
                    $user === null => 'guest',
                    ! $videoLimiter->hasAccess($user) => 'upgrade',
                    default => 'open',
                },
                'limit' => [
                    'remaining' => $videoLimiter->remaining($user),
                    'total' => $videoLimiter->limitFor($user),
                ],
            ],
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
