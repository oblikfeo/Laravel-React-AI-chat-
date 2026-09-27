<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatRequest;
use App\Models\Chat;
use App\Services\Guests\CurrentGuest;
use App\Services\Guests\GuestLimiter;
use Illuminate\Http\RedirectResponse;

class StoreChatController extends Controller
{
    /**
     * Создаёт чат с первым сообщением и открывает его.
     *
     * Ответ модели здесь не запрашивается: человек переходит в диалог
     * сразу и ждёт уже там, видя своё сообщение и индикатор набора.
     */
    public function __invoke(
        StoreChatRequest $request,
        SendMessage $sendMessage,
        GuestLimiter $limiter,
    ): RedirectResponse {
        $message = $request->string('message')->toString();
        $files = $request->file('files', []);
        $guest = CurrentGuest::get($request);

        if ($guest && ! $limiter->allows($guest)) {
            return back()->with('error', 'You have reached today\'s free limit. Sign up to keep going.');
        }

        $chat = Chat::create([
            'user_id' => $request->user()?->id,
            'guest_id' => $request->user() ? null : $guest?->id,
            'title' => $message !== ''
                ? SendMessage::titleFrom($message)
                : SendMessage::titleFrom($files[0]->getClientOriginalName()),
            // Гостю доступна одна модель: платные закрыты.
            'model_key' => $guest && ! $request->user()
                ? config('guests.model')
                : $request->modelKey(),
            'visibility' => strtolower($request->string('visibility', 'Public')->toString()),
            'last_message_at' => now(),
        ]);

        $sendMessage->store($chat, $message, $files);

        return to_route('chats.show', $chat);
    }
}
