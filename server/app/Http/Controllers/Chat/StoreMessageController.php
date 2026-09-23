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
     * Добавляет сообщение в существующий чат.
     */
    public function __invoke(
        StoreMessageRequest $request,
        Chat $chat,
        SendMessage $sendMessage,
    ): RedirectResponse {
        $this->authorize('update', $chat);

        $sendMessage->handle(
            $chat,
            $request->string('message')->toString(),
            $request->file('files', []),
        );

        return back();
    }
}
