<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Одно обращение к провайдеру и его себестоимость.
 *
 * Пишется только через UsageLedger: там решается, сколько стоит
 * обращение и на чей счёт оно идёт.
 */
class UsageRecord extends Model
{
    public const UPDATED_AT = null;

    /** Ответ текстовой модели и решение диспетчера. */
    public const CATEGORY_CHAT = 'chat';

    public const CATEGORY_IMAGE = 'image';

    /** Правка, объединение, увеличение, удаление фона. */
    public const CATEGORY_EDIT = 'edit';

    public const CATEGORY_AUDIO = 'audio';

    public const CATEGORY_VIDEO = 'video';

    /** Голосовой ввод в чате. */
    public const CATEGORY_VOICE = 'voice';

    /** Служебное: перевод описаний, тексты персонажей. */
    public const CATEGORY_SERVICE = 'service';

    public const CATEGORIES = [
        self::CATEGORY_CHAT,
        self::CATEGORY_IMAGE,
        self::CATEGORY_EDIT,
        self::CATEGORY_AUDIO,
        self::CATEGORY_VIDEO,
        self::CATEGORY_VOICE,
        self::CATEGORY_SERVICE,
    ];

    protected $fillable = [
        'user_id',
        'guest_id',
        'category',
        'model_key',
        'provider_model',
        'cost',
        'meta',
        'generation_id',
        'message_id',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'float',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    /** Записи за полуинтервал [from, to). */
    public function scopeBetween(Builder $query, mixed $from, mixed $to): Builder
    {
        return $query->where('created_at', '>=', $from)->where('created_at', '<', $to);
    }
}
