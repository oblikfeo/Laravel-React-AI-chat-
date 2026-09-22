<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:8000'],
            'visibility' => ['nullable', Rule::in(['Public', 'Private'])],
        ];
    }
}
