<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreVoiceChangeRequest;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\StudioLimiter;
use Illuminate\Http\RedirectResponse;

class StoreVoiceChangeController extends Controller
{
    /**
     * Читает загруженную запись другим голосом.
     */
    public function __invoke(
        StoreVoiceChangeRequest $request,
        QueueAudio $action,
        StudioLimiter $limiter,
        AudioStudio $studio,
    ): RedirectResponse {
        if (! $studio->isAvailable()) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
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

        $file = $request->file('recording');

        $action->voiceChange(
            $user,
            $guest,
            $file->get(),
            $file->getClientOriginalName() ?: 'recording.webm',
            $request->validated(),
        );

        return back();
    }
}
