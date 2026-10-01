<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenerationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:2000'],
            'negative_prompt' => ['nullable', 'string', 'max:1000'],
            'model' => ['required', Rule::in(array_keys(config('studio.models')))],
            'aspect_ratio' => ['required', Rule::in(array_keys(config('studio.aspect_ratios')))],
            'style' => ['nullable', Rule::in(array_keys(config('studio.styles')))],
            // Зерно задаётся вручную, когда человек хочет повторить
            // понравившийся результат с другими деталями.
            'seed' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            // Сколько вариантов одной идеи показать за раз.
            'variants' => ['nullable', 'integer', 'min:1', 'max:'.config('studio.max_variants')],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Describe what you want to see.',
        ];
    }
}
