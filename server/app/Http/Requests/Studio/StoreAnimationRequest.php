<?php

namespace App\Http\Requests\Studio;

use Illuminate\Validation\Rule;

/**
 * Оживление картинки.
 *
 * Картинка приходит либо файлом с диска, либо ссылкой на свою работу
 * из Студии. Описание движения необязательно: без него картинка
 * оживает сама.
 */
class StoreAnimationRequest extends StoreVideoRequest
{
    public function rules(): array
    {
        $model = $this->model();

        return [
            'image' => [
                'nullable',
                'required_without:source_id',
                'file',
                'image',
                'max:'.config('studio.video.max_upload'),
            ],
            'source_id' => ['nullable', 'integer', 'exists:generations,id'],
            'prompt' => ['nullable', 'string', 'max:'.config('studio.video.max_prompt')],
            'model' => ['nullable', Rule::in(array_keys(config('studio.video.models')))],
            'duration' => ['nullable', 'integer', Rule::in($model['durations'])],
            'resolution' => ['nullable', Rule::in($model['resolutions'])],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required_without' => 'Choose an image to animate.',
            'image.image' => 'Only images can be animated.',
            'image.max' => 'The image must be under 10 MB.',
        ];
    }
}
