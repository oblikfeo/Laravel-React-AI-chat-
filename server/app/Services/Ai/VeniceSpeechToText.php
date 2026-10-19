<?php

namespace App\Services\Ai;

use App\Services\Studio\GenerationFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Речь в текст на Venice AI.
 *
 * Язык модель определяет сама, поэтому русская и английская речь
 * распознаются без настройки.
 */
class VeniceSpeechToText implements SpeechToText
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeout = 60,
    ) {}

    public function isAvailable(): bool
    {
        return true;
    }

    public function transcribe(string $audio, string $filename): string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->baseUrl($this->baseUrl)
                ->attach('file', $audio, $filename)
                ->post('/audio/transcriptions', [
                    'model' => $this->model,
                    'response_format' => 'json',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Голосовой ввод: нет связи с провайдером', [
                'message' => mb_substr($exception->getMessage(), 0, 300),
            ]);

            throw GenerationFailed::temporary('Нет связи с провайдером.');
        }

        if (! $response->successful()) {
            Log::warning('Голосовой ввод: провайдер отказал', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            throw new GenerationFailed('Провайдер не смог распознать запись.');
        }

        return trim((string) $response->json('text'));
    }
}
