<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Работа в общей ленте.
 *
 * Отдаём меньше, чем в своей галерее: чужие настройки, зерно и
 * сведения о владельце посторонним не нужны.
 */
class FeedItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            // Размеры нужны раскладке: без них она не знает, какой
            // высоты будет плитка, и лента дёргается при загрузке.
            'width' => $this->width,
            'height' => $this->height,
            'model' => config("studio.models.{$this->model_key}.label"),
            'author' => $this->user?->name,
            'url' => route('studio.file', $this->resource),
            'thumbnail' => route('studio.file', $this->resource)
                .($this->thumbnail_path ? '?small=1' : ''),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
