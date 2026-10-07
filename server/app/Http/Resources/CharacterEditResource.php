<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Персонаж целиком — для его автора.
 *
 * Отдаётся только владельцу: здесь всё, что он написал для модели.
 */
class CharacterEditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...(new CharacterCardResource($this->resource))->toArray($request),

            'intro' => $this->intro,
            'instructions' => $this->instructions,
            'systemPrompt' => $this->system_prompt,
            'memories' => $this->memories ?? [],
            'modelKey' => $this->model_key,
            'temperature' => $this->temperature,
            // Что выбрал автор; в каталоге персонаж может не быть и
            // при включённом переключателе, если модель закрытая.
            'wantsPublic' => (bool) $this->is_public,

            'context' => $this->context_name ? [
                'name' => $this->context_name,
                'characters' => mb_strlen((string) $this->context_text),
                'readable' => filled($this->context_text),
            ] : null,

            'insights' => $this->insights(),
        ];
    }

    /**
     * Как персонажем пользуются.
     *
     * Считаем только цифры: чужие переписки автору не показываются.
     *
     * @return array<string, mixed>
     */
    private function insights(): array
    {
        $chats = $this->chats();

        return [
            'chats' => (clone $chats)->count(),
            'people' => (clone $chats)->whereNotNull('user_id')->distinct()->count('user_id')
                + (clone $chats)->whereNull('user_id')->distinct()->count('guest_id'),
            'messages' => Message::query()
                ->whereIn('chat_id', (clone $chats)->select('id'))
                ->where('role', Message::ROLE_USER)
                ->count(),
            'lastChatAt' => (clone $chats)->max('last_message_at'),
        ];
    }
}
