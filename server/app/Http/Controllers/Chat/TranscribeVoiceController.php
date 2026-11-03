<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\TranscribeVoiceRequest;
use App\Services\Ai\SpeechToText;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\GenerationFailed;
use App\Services\Usage\UsageLedger;
use Illuminate\Http\JsonResponse;

class TranscribeVoiceController extends Controller
{
    /**
     * Превращает запись с микрофона в текст.
     *
     * Текст возвращается в поле ввода, а не отправляется сразу: человек
     * видит, что распознано, и может поправить перед отправкой.
     */
    public function __invoke(TranscribeVoiceRequest $request, SpeechToText $speech, UsageLedger $ledger): JsonResponse
    {
        if (! $speech->isAvailable()) {
            return response()->json(['message' => 'Voice input is not available right now.'], 503);
        }

        $file = $request->file('audio');

        try {
            $text = $speech->transcribe($file->get(), $file->getClientOriginalName() ?: 'voice.webm');
        } catch (GenerationFailed) {
            return response()->json(['message' => 'Could not recognise the recording. Please try again.'], 422);
        }

        // Без длины записи оцениваем её по размеру файла: около 4 КБ
        // на секунду сжатого голоса.
        $ledger->voice(
            $request->user(),
            CurrentGuest::get($request),
            $request->integer('seconds') ?: (int) min(
                config('ai.transcription.max_seconds'),
                max(1, ceil($file->getSize() / 4096)),
            ),
        );

        if ($text === '') {
            return response()->json(['message' => 'No speech was heard in the recording.'], 422);
        }

        return response()->json(['text' => $text]);
    }
}
