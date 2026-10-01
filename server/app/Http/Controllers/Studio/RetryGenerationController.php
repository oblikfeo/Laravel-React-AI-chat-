<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CreateGeneration;
use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RetryGenerationController extends Controller
{
    /**
     * Повторяет работу: то же описание, новое зерно.
     *
     * Зерно меняется намеренно — человек просит другой вариант, а не
     * ту же самую картинку.
     */
    public function __invoke(
        Request $request,
        Generation $generation,
        CreateGeneration $action,
        StudioLimiter $limiter,
        ImageGenerator $generator,
    ): RedirectResponse {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        if (! $this->owns($generation, $request)) {
            throw new AccessDeniedHttpException();
        }

        if (! $generator->isAvailable()) {
            return back()->with('error', 'Image generation is not available yet. It is coming soon.');
        }

        if (! $limiter->allows($user, $guest)) {
            return back()->with('error', "You have reached today's limit.");
        }

        $action->handle($user, $guest, [
            'model' => $generation->model_key,
            'prompt' => $generation->prompt,
            'negative_prompt' => $generation->negative_prompt,
            'aspect_ratio' => $generation->aspect_ratio,
            'style' => $generation->style,
            'variants' => 1,
        ]);

        return back();
    }

    private function owns(Generation $generation, Request $request): bool
    {
        if ($user = $request->user()) {
            return $generation->user_id === $user->id;
        }

        $guest = CurrentGuest::get($request);

        return $guest !== null
            && $generation->guest_id === $guest->id
            && $generation->user_id === null;
    }
}
