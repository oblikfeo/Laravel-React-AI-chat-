<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CreateSpeech;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreSpeechRequest;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;

class StoreSpeechController extends Controller
{
    /**
     * Озвучивает текст.
     *
     * Доступность Студии и дневной лимит проверяет EnsureStudioAccess.
     */
    public function __invoke(StoreSpeechRequest $request, CreateSpeech $action): RedirectResponse
    {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        $action->handle($user, $guest, $request->validated());

        return back();
    }
}
