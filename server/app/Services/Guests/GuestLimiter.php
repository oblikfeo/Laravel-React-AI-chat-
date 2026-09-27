<?php

namespace App\Services\Guests;

use App\Models\Guest;
use App\Models\Message;

/**
 * Ограничения для посетителей без учётной записи.
 *
 * Два независимых предела:
 *
 * 1. На гостя — сколько сообщений в день он может отправить.
 * 2. На адрес — потолок заведомо выше, это защита от накрутки через
 *    режим инкогнито, а не «лимит человека»: за одним IP сидит весь
 *    дом или офис, и наказывать их за соседа нельзя.
 */
class GuestLimiter
{
    public function remaining(Guest $guest): int
    {
        return $guest->remaining();
    }

    public function allows(Guest $guest): bool
    {
        return $guest->remaining() > 0 && ! $this->ipExhausted($guest);
    }

    /**
     * Исчерпан ли потолок по адресу.
     *
     * Считаем сообщения всех гостей с этого адреса за сегодня.
     */
    private function ipExhausted(Guest $guest): bool
    {
        if (! $guest->ip) {
            return false;
        }

        $ids = Guest::where('ip', $guest->ip)->pluck('id');

        $sent = Message::query()
            ->where('role', Message::ROLE_USER)
            ->whereDate('created_at', today())
            ->whereIn('chat_id', function ($query) use ($ids) {
                $query->select('id')->from('chats')->whereIn('guest_id', $ids);
            })
            ->count();

        return $sent >= config('guests.daily_messages_per_ip');
    }
}
