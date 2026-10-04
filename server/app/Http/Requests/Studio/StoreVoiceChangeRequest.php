<?php

namespace App\Http\Requests\Studio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoiceChangeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Запись приходит файлом: загруженным или наговорённым
            // прямо в браузере.
            'recording' => [
                'required',
                'file',
                'mimetypes:audio/mpeg,audio/mp4,audio/wav,audio/x-wav,audio/webm,audio/ogg,audio/flac,video/webm',
                'max:'.config('studio.voice_changer.max_upload'),
            ],
            'voice' => ['nullable', Rule::in(array_keys(config('studio.voices')))],
            'remove_noise' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'recording.required' => 'Upload or record your speech first.',
            'recording.mimetypes' => 'That file is not a recording.',
            'recording.max' => 'The recording is too large.',
        ];
    }
}
