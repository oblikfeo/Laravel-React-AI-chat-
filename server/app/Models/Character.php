<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Персонаж: сохранённая роль для чата.
 */
class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'tags',
        'avatar_disk',
        'avatar_path',
        'intro',
        'instructions',
        'system_prompt',
        'context_name',
        'context_text',
        'memories',
        'model_key',
        'temperature',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'memories' => 'array',
            'temperature' => 'float',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    /**
     * Персонажи общего каталога.
     *
     * Условие одно на каталог, ленту и доступ по прямой ссылке:
     * иначе закрытого персонажа можно было бы открыть в обход списка.
     */
    public function scopeListed(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->when(
                ! config('characters.public_uncensored'),
                fn (Builder $q) => $q->whereNotIn('model_key', self::privateModels()),
            );
    }

    public function isListed(): bool
    {
        return $this->is_public
            && (config('characters.public_uncensored')
                || ! in_array($this->model_key, self::privateModels(), true));
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    /**
     * Можно ли открыть персонажа: свой или из общего каталога.
     */
    public function isOpenTo(?User $user): bool
    {
        return $this->isOwnedBy($user) || $this->isListed();
    }

    /**
     * Модели, персонажи на которых в каталог не попадают.
     *
     * @return array<int, string>
     */
    public static function privateModels(): array
    {
        return collect(config('models.list'))
            ->filter(fn (array $model) => $model['signature'] ?? false)
            ->keys()
            ->all();
    }

    /** Может ли персонаж на этой модели быть в каталоге. */
    public static function modelAllowsPublishing(string $modelKey): bool
    {
        return config('characters.public_uncensored')
            || ! in_array($modelKey, self::privateModels(), true);
    }
}
