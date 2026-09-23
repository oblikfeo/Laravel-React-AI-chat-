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
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
