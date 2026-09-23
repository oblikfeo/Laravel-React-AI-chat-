<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use App\Actions\Chat\RequestReply;
use App\Models\Chat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowChatController extends Controller
{
    /**
     * Открывает диалог со списком сообщений.
     */
    public function __invoke(Request $request, Chat $chat, RequestReply $reply): Response
    {
        $this->authorize('view', $chat);

        return Inertia::render('Chat/Show', [
            'chat' => ChatResource::make($chat),
            'messages' => MessageResource::collection(
                $chat->messages()->with('attachments')->oldest('id')->get()
            ),
            'chats' => ChatResource::collection($request->user()->chats),
            // Последнее слово за пользователем — ответ ещё не получен,
            // страница запросит его сама и покажет индикатор набора.
            'awaitingReply' => $reply->isPending($chat),
        ]);
    }
}
