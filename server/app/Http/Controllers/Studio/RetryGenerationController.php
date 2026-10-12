<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CreateGeneration;
use App\Actions\Studio\CreateSpeech;
use App\Actions\Studio\QueueAudio;
use App\Actions\Studio\QueueVideo;
use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioLimiter;
use App\Services\Studio\VideoLimiter;
use App\Services\Studio\VideoStudio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RetryGenerationController extends Controller
{
    public function __construct(
        private readonly CreateGeneration $images,
        private readonly CreateSpeech $speech,
        private readonly QueueAudio $audio,
        private readonly QueueVideo $video,
        private readonly StudioLimiter $limiter,
        private readonly VideoLimiter $videoLimiter,
        private readonly ImageGenerator $generator,
        private readonly AudioStudio $audioStudio,
        private readonly VideoStudio $videoStudio,
    ) {
    }

    /**
     * Повторяет работу.
     *
     * Зерно меняется намеренно — человек просит другой вариант, а не
     * ту же самую картинку.
     *
     * Повторять можно и звук, и видео: у них свои модели, своя
     * очередь и свой лимит, поэтому вид работы разбирается отдельно.
     */
    public function __invoke(Request $request, Generation $generation): RedirectResponse
    {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        if (! $this->owns($generation, $request)) {
            throw new AccessDeniedHttpException();
        }

        if ($generation->kind === Generation::KIND_VIDEO) {
            return $this->retryVideo($user, $generation);
        }

        $available = $generation->kind === Generation::KIND_AUDIO
            ? $this->audioStudio->isAvailable()
            : $this->generator->isAvailable();

        if (! $available) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        if (! $this->limiter->allows($user, $guest)) {
            return back()->with('error', "You have reached today's limit.");
        }

        // Смену голоса повторить нельзя: исходной записи у нас уже
        // нет, человек загружает её заново.
        if ($generation->operation === Generation::OP_VOICE_CHANGE) {
            return back()->with('error', 'Upload the recording again to redo this.');
        }

        $this->repeat($user, $guest, $generation);

        return back();
    }

    private function repeat(?User $user, ?Guest $guest, Generation $generation): void
    {
        match ($generation->operation) {
            Generation::OP_MUSIC => $this->audio->music($user, $guest, [
                'model' => $generation->model_key,
                'prompt' => $generation->prompt,
                'lyrics' => $generation->lyrics,
                'duration' => $generation->duration,
            ]),

            Generation::OP_EFFECT => $this->audio->effect($user, $guest, [
                'model' => $generation->model_key,
                'prompt' => $generation->prompt,
                'duration' => $generation->duration,
            ]),

            Generation::OP_SPEECH => $this->speech->handle($user, $guest, [
                'model' => $generation->model_key,
                'text' => $generation->prompt,
            ]),

            default => $this->images->handle($user, $guest, [
                'model' => $generation->model_key,
                'prompt' => $generation->prompt,
                'negative_prompt' => $generation->negative_prompt,
                'aspect_ratio' => $generation->aspect_ratio,
                'style' => $generation->style,
                'variants' => 1,
            ]),
        };
    }

    /**
     * Повтор ролика: условия те же, что при первом запуске.
     */
    private function retryVideo(?User $user, Generation $generation): RedirectResponse
    {
        if (! $this->videoStudio->isAvailable()) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        if (! $this->videoLimiter->hasAccess($user)) {
            return back()->with('error', 'Video is available on paid plans.');
        }

        if (! $this->videoLimiter->allows($user)) {
            return back()->with('error', "You have reached today's video limit. It resets tomorrow.");
        }

        // Картинки, которую оживляли, могло не остаться.
        if (! $this->video->again($user, $generation)) {
            return back()->with('error', 'Choose the image again to redo this.');
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
