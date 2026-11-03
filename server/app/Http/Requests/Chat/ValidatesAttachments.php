<?php

namespace App\Http\Requests\Chat;

use Illuminate\Http\UploadedFile;

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
            'files' => [
                'nullable',
                'array',
                'max:5',
                // Общий размер: больше сервер не примет вовсе, и человек
                // увидел бы голую страницу ошибки вместо подсказки.
                function (string $attribute, mixed $files, \Closure $fail) {
                    $total = collect(is_array($files) ? $files : [])
                        ->sum(fn ($file) => $file instanceof UploadedFile ? $file->getSize() : 0);

                    if ($total > 25 * 1024 * 1024) {
                        $fail('Files together must be under 25 MB.');
                    }
                },
            ],
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
