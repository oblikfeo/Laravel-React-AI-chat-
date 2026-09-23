<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    use ValidatesAttachments;

    public function rules(): array
    {
        return array_merge([
            'message' => ['required_without:files', 'nullable', 'string', 'max:8000'],
        ], $this->attachmentRules());
    }

    public function messages(): array
    {
        return array_merge([
            'message.required_without' => 'Type a message or attach a file.',
        ], $this->attachmentMessages());
    }
}
