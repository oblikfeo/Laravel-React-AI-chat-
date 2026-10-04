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
            'kind' => $this->kind,
            'operation' => $this->operation,
            'status' => $this->status,
            'prompt' => $this->prompt,
            'negativePrompt' => $this->negative_prompt,
            'aspectRatio' => $this->aspect_ratio,
            'style' => $this->style,
            'seed' => $this->seed,
            'duration' => $this->duration,
            // Сколько примерно ждать: без оценки остаётся только
            // бесконечная крутилка.
            'expectedMs' => $this->expected_ms,
            'variants' => $this->variants,
            'mime' => $this->mime,
            'width' => $this->width,
            'height' => $this->height,
            // Название модели у провайдера наружу не отдаём, только ярлык.
            'model' => config("studio.models.{$this->model_key}.label")
                ?? config("studio.speech.models.{$this->model_key}.label")
                ?? config("studio.music.models.{$this->model_key}.label")
                ?? config("studio.effects.models.{$this->model_key}.label"),
            'modelKey' => $this->model_key,
            'url' => $this->isReady()
                ? route('studio.file', $this->resource)
                : null,
            // Для ленты: уменьшенная копия, если она есть.
            'thumbnail' => $this->isReady()
                ? route('studio.file', $this->resource)
                    .($this->thumbnail_path ? '?small=1' : '')
                : null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
