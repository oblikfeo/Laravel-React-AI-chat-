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
     *
     * Ответ модели здесь не запрашивается: человек переходит в диалог
     * сразу и ждёт уже там, видя своё сообщение и индикатор набора.
     */
    public function __invoke(StoreChatRequest $request, SendMessage $sendMessage): RedirectResponse
    {
        $message = $request->string('message')->toString();
        $files = $request->file('files', []);

        $chat = $request->user()->chats()->create([
            'title' => $message !== ''
                ? SendMessage::titleFrom($message)
                : SendMessage::titleFrom($files[0]->getClientOriginalName()),
            'model_key' => $request->modelKey(),
            'visibility' => strtolower($request->string('visibility', 'Public')->toString()),
            'last_message_at' => now(),
        ]);

        $sendMessage->store($chat, $message, $files);

        return to_route('chats.show', $chat);
    }
}
