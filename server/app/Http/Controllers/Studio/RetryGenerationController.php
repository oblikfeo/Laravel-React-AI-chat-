<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CreateGeneration;
use App\Actions\Studio\CreateSpeech;
use App\Actions\Studio\QueueAudio;
use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RetryGenerationController extends Controller
{
    /**
     * Повторяет работу.
     *
     * Зерно меняется намеренно — человек просит другой вариант, а не
     * ту же самую картинку.
     *
     * Повторять можно и звук: у него свои модели и своя очередь,
     * поэтому вид работы разбирается отдельно.
     */
    public function __invoke(
        Request $request,
        Generation $generation,
        CreateGeneration $images,
        CreateSpeech $speech,
        QueueAudio $audio,
        StudioLimiter $limiter,
        ImageGenerator $generator,
        AudioStudio $studio,
    ): RedirectResponse {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        if (! $this->owns($generation, $request)) {
            throw new AccessDeniedHttpException();
        }

        $isAudio = $generation->kind === Generation::KIND_AUDIO;
        $available = $isAudio ? $studio->isAvailable() : $generator->isAvailable();

        if (! $available) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        if (! $limiter->allows($user, $guest)) {
            return back()->with('error', "You have reached today's limit.");
        }

        match ($generation->operation) {
            Generation::OP_MUSIC => $audio->music($user, $guest, [
                'model' => $generation->model_key,
                'prompt' => $generation->prompt,
                'lyrics' => $generation->lyrics,
                'duration' => $generation->duration,
            ]),

            Generation::OP_EFFECT => $audio->effect($user, $guest, [
                'model' => $generation->model_key,
                'prompt' => $generation->prompt,
                'duration' => $generation->duration,
            ]),

            Generation::OP_SPEECH => $speech->handle($user, $guest, [
                'model' => $generation->model_key,
                'text' => $generation->prompt,
            ]),

            // Смену голоса повторить нельзя: исходной записи у нас
            // уже нет, человек загружает её заново.
            Generation::OP_VOICE_CHANGE => null,

            default => $images->handle($user, $guest, [
                'model' => $generation->model_key,
                'prompt' => $generation->prompt,
                'negative_prompt' => $generation->negative_prompt,
                'aspect_ratio' => $generation->aspect_ratio,
                'style' => $generation->style,
                'variants' => 1,
            ]),
        };

        if ($generation->operation === Generation::OP_VOICE_CHANGE) {
            return back()->with('error', 'Upload the recording again to redo this.');
        }

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
