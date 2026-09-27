<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\Guests\ChatOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ShowAttachmentController extends Controller
{
    /**
     * Отдаёт приложенный файл.
     *
     * Файлы лежат вне публичной папки, поэтому доступ идёт через
     * приложение: чужое вложение открыть нельзя.
     */
    public function __invoke(Request $request, Attachment $attachment): StreamedResponse
    {
        if (! ChatOwnership::owns($request, $attachment->message->chat)) {
            throw new AccessDeniedHttpException();
        }

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->name,
        );
    }
}
