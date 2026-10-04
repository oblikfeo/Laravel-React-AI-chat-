<?php

namespace App\Http\Requests\Studio;

use App\Models\Generation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Правка изображения.
 *
 * Исходник приходит либо ссылкой на свою работу, либо загруженным
 * файлом: человек может править и чужую картинку со своего диска.
 */
class EditGenerationRequest extends FormRequest
{
    public function rules(): array
    {
        $operations = [
            Generation::OP_EDIT,
            Generation::OP_COMBINE,
            Generation::OP_UPSCALE,
            Generation::OP_BACKGROUND,
        ];

        return [
            // Человек решает, показывать работу в общей ленте.
            'is_public' => ['nullable', 'boolean'],
            'operation' => ['required', Rule::in($operations)],

            // Описание нужно там, где человек говорит, что изменить.
            'prompt' => [
                Rule::requiredIf(fn () => in_array(
                    $this->input('operation'),
                    [Generation::OP_EDIT, Generation::OP_COMBINE],
                    true,
                )),
                'nullable',
                'string',
                'max:2000',
            ],

            // Объединение берёт несколько работ, остальные инструменты
            // одну: список подходит обоим случаям.
            'source_ids' => ['nullable', 'array', 'max:'.config('studio.edit.max_combine')],
            'source_ids.*' => ['integer', 'exists:generations,id'],

            'images' => ['nullable', 'array', 'max:'.config('studio.edit.max_combine')],
            'images.*' => ['file', 'image', 'max:10240'],

            'aspect_ratio' => ['nullable', Rule::in(array_keys(config('studio.aspect_ratios')))],
            'scale' => ['nullable', Rule::in(config('studio.edit.scales'))],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Describe the changes you want.',
            'images.*.image' => 'Only images can be edited.',
            'images.*.max' => 'Each image must be under 10 MB.',
        ];
    }
}
