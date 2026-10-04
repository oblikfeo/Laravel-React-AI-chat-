<?php

namespace App\Actions\Chat;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Generation;
use App\Models\Guest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Открывает чат с работой из общей ленты.
 *
 * Человек увидел чужую картинку и хочет её обсудить: заводим диалог,
 * в котором она уже приложена.
 */
class StartChatFromWork
{
    public function handle(?User $user, ?Guest $guest, Generation $work): Chat
    {
        $chat = Chat::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'title' => Str::limit($work->prompt, 60),
            'model_key' => config('models.default'),
            'last_message_at' => now(),
        ]);

        $message = $chat->messages()->create([
            'role' => Message::ROLE_USER,
            'content' => '',
        ]);

        // Файл копируем: чужая работа может быть удалена, а вложение
        // в диалоге должно остаться.
        $source = Storage::disk($work->disk);
        $extension = pathinfo($work->path, PATHINFO_EXTENSION) ?: 'png';
        $path = "attachments/{$chat->id}/".Str::uuid().'.'.$extension;

        Storage::disk('local')->put($path, $source->get($work->path));

        Attachment::create([
            'message_id' => $message->id,
            'disk' => 'local',
            'path' => $path,
            'name' => Str::limit($work->prompt, 40, '').'.'.$extension,
            'mime' => $work->mime ?: 'image/png',
            'size' => Storage::disk('local')->size($path),
        ]);

        return $chat;
    }
}
