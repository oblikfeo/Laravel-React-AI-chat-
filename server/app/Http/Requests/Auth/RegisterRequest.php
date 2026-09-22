<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Регистрация строго в три поля: имя, почта, пароль.
     * Подтверждающих полей нет по требованию заказчика.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please choose a password.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }
}
