<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatRequest;
use Illuminate\Http\RedirectResponse;

class StoreChatController extends Controller
{
    /**
     * Создаёт чат с первым сообщением и открывает его.
     */
    public function __invoke(StoreChatRequest $request, SendMessage $sendMessage): RedirectResponse
    {
        $message = $request->string('message')->toString();

        $chat = $request->user()->chats()->create([
            'title' => SendMessage::titleFrom($message),
            'visibility' => strtolower($request->string('visibility', 'Public')->toString()),
            'last_message_at' => now(),
        ]);

        $sendMessage->handle($chat, $message);

        return to_route('chats.show', $chat);
    }
}
