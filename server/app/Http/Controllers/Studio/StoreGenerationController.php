<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CreateGeneration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreGenerationRequest;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioLimiter;
use Illuminate\Http\RedirectResponse;

class StoreGenerationController extends Controller
{
    /**
     * Создаёт изображение по описанию.
     */
    public function __invoke(
        StoreGenerationRequest $request,
        CreateGeneration $action,
        StudioLimiter $limiter,
        ImageGenerator $generator,
    ): RedirectResponse {
        if (! $generator->isAvailable()) {
            return back()->with('error', 'Image generation is not available yet. It is coming soon.');
        }

        $user = $request->user();
        $guest = CurrentGuest::get($request);

        if (! $limiter->allows($user, $guest)) {
            return back()->with(
                'error',
                $user
                    ? "You have reached today's limit. It resets tomorrow."
                    : "You have reached today's free limit. Sign up to create more."
            );
        }

        $action->handle($user, $guest, $request->validated());

        return back();
    }
}
