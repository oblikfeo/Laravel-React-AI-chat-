<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreVoiceChangeRequest;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;

class StoreVoiceChangeController extends Controller
{
    /**
     * Ставит в работу смену голоса в записи.
     *
     * Доступность Студии и дневной лимит проверяет EnsureStudioAccess.
     */
    public function __invoke(StoreVoiceChangeRequest $request, QueueAudio $action): RedirectResponse
    {
        $file = $request->file('recording');

        $action->voiceChange(
            $request->user(),
            CurrentGuest::get($request),
            $file->get(),
            $file->getClientOriginalName() ?: 'recording.webm',
            $request->validated(),
        );

        return back();
    }
}
