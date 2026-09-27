<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Посетитель без учётной записи.
 *
 * Пользуется чатом ограниченно: одна модель и несколько сообщений
 * в день. Когда регистрируется, его переписка переходит в аккаунт.
 */
class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'fingerprint',
        'ip',
        'user_agent',
        'user_id',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class)->latest('last_message_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Сколько сообщений отправлено за сегодня. */
    public function messagesToday(): int
    {
        return Message::query()
            ->where('role', Message::ROLE_USER)
            ->whereIn('chat_id', $this->chats()->select('id'))
            ->whereDate('created_at', today())
            ->count();
    }

    public function remaining(): int
    {
        return max(0, config('guests.daily_messages') - $this->messagesToday());
    }
}
