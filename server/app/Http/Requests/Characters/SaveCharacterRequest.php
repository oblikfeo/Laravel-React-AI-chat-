<?php

namespace App\Http\Requests\Characters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Поля персонажа.
 *
 * Один набор правил на создание и правку: форма у них общая.
 */
class SaveCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $limits = config('characters.limits');

        return [
            'name' => ['required', 'string', 'max:'.$limits['name']],
            'description' => ['nullable', 'string', 'max:'.$limits['description']],

            'tags' => ['nullable', 'array', 'max:'.$limits['tags']],
            'tags.*' => ['nullable', 'string', 'max:'.$limits['tag_length']],

            'intro' => ['nullable', 'string', 'max:'.$limits['intro']],
            'instructions' => ['required', 'string', 'max:'.$limits['instructions']],
            'system_prompt' => ['nullable', 'string', 'max:'.$limits['system_prompt']],

            'memories' => ['nullable', 'array', 'max:'.$limits['memories']],
            'memories.*' => ['nullable', 'string', 'max:'.$limits['memory_length']],

            'model' => ['required', Rule::in(array_keys(config('models.list')))],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'is_public' => ['nullable', 'boolean'],

            'avatar' => ['nullable', 'file', 'image', 'max:'.$limits['avatar_kb']],
            'avatar_generation_id' => ['nullable', 'integer'],
            'remove_avatar' => ['nullable', 'boolean'],

            // Документ со сведениями: только то, из чего читается текст.
            'context' => [
                'nullable',
                'file',
                'mimetypes:application/pdf,text/plain,text/markdown,text/x-markdown',
                'max:'.$limits['context_kb'],
            ],
            'remove_context' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Give your character a name.',
            'instructions.required' => 'Describe your character: this becomes its personality.',
            'avatar.image' => 'The avatar must be an image.',
            'avatar.max' => 'The avatar must be under 5 MB.',
            'context.mimetypes' => 'Upload a PDF, TXT or MD file.',
            'context.max' => 'The file must be under 5 MB.',
            'tags.max' => 'Up to five tags.',
        ];
    }
}
