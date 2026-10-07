<?php

namespace App\Actions\Characters;

use App\Models\Character;
use App\Models\Chat;
use App\Models\Guest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Открывает диалог с персонажем.
 *
 * Диалог у человека с персонажем один и постоянный: каждый заход
 * продолжает прежнюю переписку, а не заводит новую. Поэтому сначала
 * ищем уже начатый и создаём, только если его нет.
 */
class StartCharacterChat
{
    public function handle(Character $character, ?User $user, ?Guest $guest): Chat
    {
        $existing = Chat::query()
            ->where('character_id', $character->id)
            ->when(
                $user,
                fn ($query) => $query->where('user_id', $user->id),
                fn ($query) => $query
                    ->whereNull('user_id')
                    ->where('guest_id', $guest?->id),
            )
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $chat = Chat::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'character_id' => $character->id,
            'title' => Str::limit($character->name, 48, '…'),
            // Гостю платные модели закрыты: у него остаётся своя.
            'model_key' => $user ? $character->model_key : config('guests.model'),
            'visibility' => 'private',
            'last_message_at' => now(),
        ]);

        // Первая реплика персонажа: с неё начинается разговор, чтобы
        // человек не смотрел в пустое окно.
        if (filled($character->intro)) {
            $chat->messages()->create([
                'role' => Message::ROLE_ASSISTANT,
                'content' => $character->intro,
            ]);
        }

        return $chat;
    }
}
