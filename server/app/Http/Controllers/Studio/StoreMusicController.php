<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreMusicRequest;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;

class StoreMusicController extends Controller
{
    /**
     * Ставит музыку в работу.
     *
     * Доступность Студии и дневной лимит проверяет EnsureStudioAccess.
     */
    public function __invoke(StoreMusicRequest $request, QueueAudio $action): RedirectResponse
    {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        $action->music($user, $guest, $request->validated());

        return back();
    }
}
