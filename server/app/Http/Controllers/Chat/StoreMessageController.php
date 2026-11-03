<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Chat;
use App\Services\Guests\ChatOwnership;
use App\Services\Guests\CurrentGuest;
use App\Services\Usage\UsageGate;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StoreMessageController extends Controller
{
    /**
     * Добавляет сообщение в чат.
     *
     * Ответ запрашивается отдельно, см. ReplyController: так сообщение
     * появляется на экране мгновенно, а ожидание видно индикатором.
     */
    public function __invoke(
        StoreMessageRequest $request,
        Chat $chat,
        SendMessage $sendMessage,
        UsageGate $gate,
    ): RedirectResponse {
        if (! ChatOwnership::owns($request, $chat)) {
            throw new AccessDeniedHttpException;
        }

        $guest = CurrentGuest::get($request);

        if ($denial = $gate->chat($request->user(), $guest)) {
            return back()->with('error', $denial);
        }

        $sendMessage->store(
            $chat,
            $request->string('message')->toString(),
            $request->file('files', []),
        );

        return back();
    }
}
