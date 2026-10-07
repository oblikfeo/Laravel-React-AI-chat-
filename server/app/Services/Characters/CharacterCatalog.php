<?php

namespace App\Services\Characters;

use App\Models\Character;
use Illuminate\Database\Eloquent\Builder;

/**
 * Общий каталог персонажей.
 *
 * Один запрос на раздел персонажей и на ленту: условия, по которым
 * персонаж виден всем, должны совпадать везде.
 */
class CharacterCatalog
{
    /**
     * Персонажи каталога: сначала те, с кем больше разговаривают.
     */
    public function query(?string $search = null, ?string $tag = null): Builder
    {
        return Character::query()
            ->listed()
            ->with('user:id,name')
            ->withCount('chats')
            ->when(filled($search), function (Builder $query) use ($search) {
                $like = '%'.mb_strtolower(trim($search)).'%';

                $query->where(fn (Builder $q) => $q
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$like]));
            })
            ->when(filled($tag), fn (Builder $query) => $query
                ->whereJsonContains('tags', $tag))
            ->orderByDesc('chats_count')
            ->orderByDesc('id');
    }

    /**
     * Теги для фильтра: что встречается в каталоге чаще всего.
     *
     * @return array<int, string>
     */
    public function popularTags(int $limit = 12): array
    {
        return Character::query()
            ->listed()
            ->whereNotNull('tags')
            ->latest('id')
            ->limit(300)
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take($limit)
            ->values()
            ->all();
    }
}
