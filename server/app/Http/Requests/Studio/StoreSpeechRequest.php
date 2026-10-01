<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpeechRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:'.config('studio.speech.max_characters')],
            'model' => ['nullable', Rule::in(array_keys(config('studio.speech.models')))],
            'voice' => ['nullable', 'string', 'max:100'],
            'speed' => ['nullable', 'numeric', 'min:0.25', 'max:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Enter the text to read out loud.',
        ];
    }
}
