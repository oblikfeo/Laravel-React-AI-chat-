<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        // Владельцем может быть пользователь или гость, поэтому оба
        // поля заполняются явно при создании чата.
        'user_id',
        'guest_id',
        'character_id',
        'title',
        'model_key',
        'visibility',
        'is_pinned',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** Персонаж, с которым идёт диалог, если это диалог с персонажем. */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Какой моделью отвечать.
     *
     * В диалоге с персонажем модель задаёт его автор, и её смена
     * должна действовать сразу. Гостю платные модели закрыты, поэтому
     * у него остаётся та, с которой чат создан.
     */
    public function effectiveModelKey(): string
    {
        if ($this->character && $this->user_id !== null) {
            return $this->character->model_key;
        }

        return (string) $this->model_key;
    }
}
