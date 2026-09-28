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

        return Inertia::render('Studio/Index', [
            'generations' => GenerationResource::collection(
                Generation::query()
                    ->ownedBy($user, $guest)
                    ->latest('id')
                    ->limit(60)
                    ->get()
            ),

            'models' => StudioCatalog::models(),
            'aspectRatios' => StudioCatalog::aspectRatios(),
            'styles' => StudioCatalog::styles(),
            'defaultModel' => config('studio.default_model'),

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
