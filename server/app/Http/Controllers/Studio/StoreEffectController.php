<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreEffectRequest;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;

class StoreEffectController extends Controller
{
    /**
     * Ставит звуковой эффект в работу.
     *
     * Доступность Студии и дневной лимит проверяет EnsureStudioAccess.
     */
    public function __invoke(StoreEffectRequest $request, QueueAudio $action): RedirectResponse
    {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        $action->effect($user, $guest, $request->validated());

        return back();
    }
}
