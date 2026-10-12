<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ролик по описанию.
 */
class StoreVideoRequest extends FormRequest
{
    public function rules(): array
    {
        $model = $this->model();

        return [
            'prompt' => ['required', 'string', 'min:3', 'max:'.config('studio.video.max_prompt')],
            'model' => ['nullable', Rule::in(array_keys(config('studio.video.models')))],
            'duration' => ['nullable', 'integer', Rule::in($model['durations'])],
            'resolution' => ['nullable', Rule::in($model['resolutions'])],
            'aspect_ratio' => ['nullable', Rule::in($model['aspect_ratios'])],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Describe the video you want to make.',
            'prompt.min' => 'Add a few more words about the video.',
        ];
    }

    /**
     * Выбранная модель: от неё зависят допустимые длина и размер.
     *
     * @return array<string, mixed>
     */
    protected function model(): array
    {
        return config('studio.video.models.'.$this->input('model'))
            ?? config('studio.video.models.'.config('studio.video.default_model'));
    }
}
