<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'model' => $this->model,
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'size' => $a->size,
                    'isImage' => $a->isImage(),
                    'url' => route('attachments.show', $a),
                ])
                ->values()
                ->all(), []),
            // Картинка, которой чат ответил.
            'image' => $this->whenLoaded('generation', fn () => $this->generation?->isReady() ? [
                'id' => $this->generation->id,
                'url' => route('studio.file', $this->generation),
                'prompt' => $this->generation->prompt,
            ] : null),
            'intent' => $this->intent,
            // Карточка «сделать это в Студии»: форма там уже заполнена.
            'studioLink' => $this->studio_link ? [
                'tab' => $this->studio_link['tab'],
                'operation' => $this->studio_link['operation'],
                'prompt' => $this->studio_link['prompt'],
                'url' => route('studio', array_filter([
                    'compose' => $this->studio_link['tab'],
                    'mode' => $this->studio_link['operation'],
                    'prompt' => $this->studio_link['prompt'],
                    'attachment' => $this->studio_link['attachment'] ?? null,
                    'work' => $this->studio_link['work'] ?? null,
                ])),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
