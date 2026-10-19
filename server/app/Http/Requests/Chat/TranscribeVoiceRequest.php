<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Запись с микрофона для голосового ввода.
 */
class TranscribeVoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'audio' => [
                'required',
                'file',
                'max:'.config('ai.transcription.max_kb'),
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,video/mp4,audio/mpeg,audio/wav,audio/x-wav,audio/aac,audio/x-m4a',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required' => 'Nothing was recorded.',
            'audio.max' => 'The recording is too long.',
            'audio.mimetypes' => 'This recording format is not supported.',
        ];
    }
}
