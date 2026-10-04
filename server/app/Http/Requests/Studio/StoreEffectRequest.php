<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEffectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:'.config('studio.effects.max_prompt')],
            'model' => ['nullable', Rule::in(array_keys(config('studio.effects.models')))],
            'duration' => ['nullable', 'integer', 'min:1', 'max:180'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Describe the sound you need.',
        ];
    }
}
