<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Models\Chat;
use Illuminate\Http\RedirectResponse;

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
    ): RedirectResponse {
        $this->authorize('update', $chat);

        $sendMessage->store(
            $chat,
            $request->string('message')->toString(),
            $request->file('files', []),
        );

        return back();
    }
}
