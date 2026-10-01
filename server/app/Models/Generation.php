<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Работа, созданная в Студии.
 */
class Generation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    public const KIND_IMAGE = 'image';
    public const KIND_AUDIO = 'audio';
    public const KIND_VIDEO = 'video';

    /** Инструменты Студии. */
    public const OP_GENERATE = 'generate';
    public const OP_EDIT = 'edit';
    public const OP_COMBINE = 'combine';
    public const OP_UPSCALE = 'upscale';
    public const OP_BACKGROUND = 'background_remove';
    public const OP_SPEECH = 'speech';
    public const OP_MUSIC = 'music';

    protected $fillable = [
        'user_id',
        'guest_id',
        'kind',
        'operation',
        'source_generation_id',
        'source_path',
        'model_key',
        'status',
        'prompt',
        'negative_prompt',
        'aspect_ratio',
        'style',
        'seed',
        'variants',
        'disk',
        'path',
        'mime',
        'width',
        'height',
        'failure_reason',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'variants' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'completed_at' => 'datetime',
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

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY && $this->path !== null;
    }

    /** Работы владельца: пользователя или гостя. */
    public function scopeOwnedBy(Builder $query, ?User $user, ?Guest $guest): Builder
    {
        if ($user) {
            return $query->where('user_id', $user->id);
        }

        if ($guest) {
            return $query->where('guest_id', $guest->id)->whereNull('user_id');
        }

        // Ничей запрос не должен вернуть чужие работы.
        return $query->whereRaw('1 = 0');
    }
}
