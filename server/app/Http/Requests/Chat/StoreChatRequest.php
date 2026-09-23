<?php

namespace App\Http\Requests\Chat;

use App\Services\Ai\ModelCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatRequest extends FormRequest
{
    use ValidatesAttachments;

    public function rules(): array
    {
        return array_merge([
            // Сообщение может быть пустым, если приложен файл:
            // «посмотри этот документ» без слов — обычный сценарий.
            'message' => ['required_without:files', 'nullable', 'string', 'max:8000'],
            'model' => ['nullable', Rule::in(array_keys(config('models.list')))],
            'visibility' => ['nullable', Rule::in(['Public', 'Private'])],
        ], $this->attachmentRules());
    }

    public function messages(): array
    {
        return array_merge([
            'message.required_without' => 'Type a message or attach a file.',
        ], $this->attachmentMessages());
    }

    public function modelKey(): string
    {
        return ModelCatalog::resolve($this->string('model')->toString() ?: null);
    }
}
