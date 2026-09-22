<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:8000'],
        ];
    }
}
