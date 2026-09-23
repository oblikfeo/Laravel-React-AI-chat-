<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'modelKey' => $this->model_key,
            'visibility' => $this->visibility,
            'is_pinned' => $this->is_pinned,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
        ];
    }
}
