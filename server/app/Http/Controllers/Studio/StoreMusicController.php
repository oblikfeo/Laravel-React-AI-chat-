<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreMusicRequest;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\StudioLimiter;
use Illuminate\Http\RedirectResponse;

class StoreMusicController extends Controller
{
    /**
     * Ставит музыку в работу.
     */
    public function __invoke(
        StoreMusicRequest $request,
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

        $action->music($user, $guest, $request->validated());

        return back();
    }
}
