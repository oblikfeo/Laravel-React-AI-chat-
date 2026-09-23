<?php

namespace App\Http\Requests\Chat;

/**
 * Правила для приложенных файлов.
 *
 * Список типов закрытый: принимаем только то, из чего умеем достать
 * содержимое для модели. Исполняемые файлы и архивы не принимаем —
 * пользы от них модели нет, а риск есть.
 */
trait ValidatesAttachments
{
    /** @return array<string, mixed> */
    protected function attachmentRules(): array
    {
        return [
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,image/gif,'
                .'application/pdf,text/plain,text/markdown,text/csv,'
                .'application/json,application/xml,text/xml',
            ],
        ];
    }

    /** @return array<string, string> */
    protected function attachmentMessages(): array
    {
        return [
            'files.max' => 'You can attach up to 5 files.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
            'files.*.mimetypes' => 'This file type is not supported.',
        ];
    }
}
