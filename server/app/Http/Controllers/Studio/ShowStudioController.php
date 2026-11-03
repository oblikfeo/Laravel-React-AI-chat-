<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioCatalog;
use App\Services\Studio\StudioCompose;
use App\Services\Usage\StudioUsage;
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
        StudioUsage $usage,
        ImageGenerator $generator,
        StudioCompose $compose,
    ): Response {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        ['share' => $share, 'limits' => $limits, 'caps' => $caps] = $usage->for($user, $guest);

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

            'models' => StudioCatalog::models($user, $share),
            'aspectRatios' => StudioCatalog::aspectRatios(),
            'styles' => StudioCatalog::styles(),
            'speechModels' => StudioCatalog::speechModels($user, $share),
            'musicModels' => StudioCatalog::musicModels($user, $share, $caps),
            'effectModels' => StudioCatalog::effectModels($user, $share, $caps),
            'videoModels' => StudioCatalog::videoModels($share),
            'defaultVideoModel' => config('studio.video.default_model'),
            'editPrices' => StudioCatalog::editPrices($share),

            // Видео — только на платных тарифах. Гостю и бесплатному
            // тарифу показываем, что оно есть и как его получить, а не
            // прячем вкладку. Бюджет у видео общий со всей Студией.
            'video' => [
                'access' => match (true) {
                    $user === null => 'guest',
                    ! $user->hasVideo() => 'upgrade',
                    default => 'open',
                },
                'limit' => $limits['video'],
            ],
            'voices' => StudioCatalog::voices(),
            'defaultVoice' => config('studio.default_voice'),
            'defaultMusicModel' => config('studio.music.default_model'),
            'defaultEffectModel' => config('studio.effects.default_model'),
            'maxLyrics' => (int) config('studio.music.max_lyrics'),
            'defaultModel' => config('studio.default_model'),
            'defaultSpeechModel' => config('studio.speech.default_model'),
            // Без подписки дорогие настройки закрыты, см. caps в
            // config/plans.php: формы показывают их под замком.
            'maxVariants' => (int) ($caps['variants'] ?? config('studio.max_variants')),
            'maxSpeech' => (int) ($caps['speech_characters'] ?? config('studio.speech.max_characters')),
            'voiceChange' => $caps['voice_change'] ?? true,
            'voiceChangePrice' => StudioCatalog::price(config('studio.voice_changer'), $share),
            'maxSeed' => (int) config('studio.max_seed'),
            'upscaleScales' => $caps['upscale_scales'] ?? config('studio.edit.scales'),
            'maxCombine' => (int) config('studio.edit.max_combine'),

            // Пока провайдер не открыл доступ, интерфейс говорит, что
            // Студия скоро заработает, вместо ошибки на весь экран.
            'studioReady' => $generator->isAvailable(),

            // Остаток каждого раздела в процентах и когда он обновится.
            'limits' => $limits,
        ]);
    }
}
