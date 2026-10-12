<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueVideo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreVideoRequest;
use Illuminate\Http\RedirectResponse;

class StoreVideoController extends Controller
{
    /**
     * Ставит в работу ролик по описанию.
     *
     * Кому можно снимать и сколько, проверяет EnsureVideoAccess.
     */
    public function __invoke(StoreVideoRequest $request, QueueVideo $action): RedirectResponse
    {
        $action->fromText($request->user(), $request->validated());

        return back();
    }
}
