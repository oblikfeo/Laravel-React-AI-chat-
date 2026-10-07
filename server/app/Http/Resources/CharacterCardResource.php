<?php

namespace App\Http\Resources;

use App\Services\Ai\ModelCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Карточка персонажа: то, что видно всем.
 *
 * Инструкции, память и документ сюда не попадают никогда: это
 * работа автора, и посторонним её показывать нельзя.
 */
class CharacterCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'tags' => $this->tags ?? [],
            'avatar' => $this->avatar_path
                // Метка времени в адресе: после смены аватара браузер
                // не покажет старый из своей памяти.
                ? route('characters.avatar', $this->resource).'?v='.$this->updated_at?->timestamp
                : null,
            'author' => $this->whenLoaded('user', fn () => $this->user?->name),
            // Название модели у провайдера наружу не отдаём, только ярлык.
            'model' => ModelCatalog::labelOf($this->model_key),
            'chats' => $this->whenCounted('chats'),
            'isMine' => $this->isOwnedBy($request->user()),
            'isPublic' => $this->isListed(),
        ];
    }
}
