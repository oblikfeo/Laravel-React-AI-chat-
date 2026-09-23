<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShowAttachmentController extends Controller
{
    /**
     * Отдаёт приложенный файл.
     *
     * Файлы лежат вне публичной папки, поэтому доступ идёт через
     * приложение: чужое вложение открыть нельзя.
     */
    public function __invoke(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment->message->chat);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->name,
        );
    }
}
