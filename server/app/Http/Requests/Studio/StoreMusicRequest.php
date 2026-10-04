<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMusicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:10', 'max:'.config('studio.music.max_prompt')],
            'model' => ['nullable', Rule::in(array_keys(config('studio.music.models')))],
            'lyrics' => ['nullable', 'string', 'max:'.config('studio.music.max_lyrics')],
            'duration' => ['nullable', 'integer', 'min:10', 'max:600'],
            'instrumental' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Песня без слов — не песня: модель их ждёт и откажет.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $model = config('studio.music.models.'.$this->input('model'));

            if (($model['lyrics_required'] ?? false) && blank($this->input('lyrics'))) {
                $validator->errors()->add('lyrics', 'This model needs lyrics to sing.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Describe the music you want to make.',
            'prompt.min' => 'Add a few more words about the music.',
        ];
    }
}
