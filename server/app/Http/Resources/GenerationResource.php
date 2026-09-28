<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'prompt' => $this->prompt,
            'negativePrompt' => $this->negative_prompt,
            'aspectRatio' => $this->aspect_ratio,
            'style' => $this->style,
            'seed' => $this->seed,
            'width' => $this->width,
            'height' => $this->height,
            // Название модели у провайдера наружу не отдаём, только ярлык.
            'model' => config("studio.models.{$this->model_key}.label"),
            'modelKey' => $this->model_key,
            'url' => $this->isReady()
                ? route('studio.file', $this->resource)
                : null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
