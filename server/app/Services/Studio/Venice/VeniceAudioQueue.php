<?php

namespace App\Services\Studio\Venice;

use App\Services\Studio\GenerationFailed;
use App\Services\Studio\QueuedJob;
use App\Services\Studio\QueueResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Долгие звуковые задачи у Venice.
 *
 * Музыка, эффекты и смена голоса считаются дольше, чем человек готов
 * ждать ответа страницы, поэтому провайдер принимает запрос в очередь
 * и отдаёт номер задачи. Результат забирается отдельно.
 */
class VeniceAudioQueue
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 60,
    ) {
    }

    /**
     * Ставит музыку или эффект в очередь.
     */
    public function queueAudio(array $payload): QueuedJob
    {
        return $this->enqueue('/audio/queue', $payload);
    }

    /**
     * Ставит смену голоса в очередь.
     *
     * Запись уходит файлом, поэтому запрос составной.
     *
     * @param  string  $recording  содержимое исходного файла
     */
    public function queueVoiceChange(array $payload, string $recording, string $filename): QueuedJob
    {
        $response = $this->request()
            ->attach('file', $recording, $filename)
            ->post('/audio/voice-changer/queue', $payload);

        $this->guard($response, '/audio/voice-changer/queue');

        return $this->jobFrom($response->json() ?? []);
    }

    /**
     * Спрашивает готовность.
     *
     * Пока задача считается, провайдер отвечает состоянием; когда
     * готова — самим файлом.
     */
    public function retrieve(string $queueId, bool $voiceChange = false): QueueResult
    {
        $path = $voiceChange
            ? '/audio/voice-changer/retrieve'
            : '/audio/retrieve';

        $response = $this->request()->get($path, ['queue_id' => $queueId]);

        $this->guard($response, $path);

        $type = $response->header('content-type');

        // JSON означает, что работа ещё идёт: готовый результат
        // приходит самим файлом.
        if (str_contains($type, 'application/json')) {
            $body = $response->json() ?? [];

            return QueueResult::pending(
                status: $body['status'] ?? 'PROCESSING',
                expectedMs: isset($body['average_execution_time'])
                    ? (int) $body['average_execution_time']
                    : null,
            );
        }

        return QueueResult::ready(
            contents: $response->body(),
            mime: $this->cleanMime($type),
        );
    }

    private function enqueue(string $path, array $payload): QueuedJob
    {
        $response = $this->request()->asJson()->post($path, $payload);

        $this->guard($response, $path);

        return $this->jobFrom($response->json() ?? []);
    }

    private function jobFrom(array $body): QueuedJob
    {
        $id = $body['queue_id'] ?? null;

        if (! $id) {
            throw new GenerationFailed('Провайдер не принял задачу в работу.');
        }

        return new QueuedJob(
            id: $id,
            expectedMs: isset($body['average_execution_time'])
                ? (int) $body['average_execution_time']
                : null,
        );
    }

    /**
     * Тип файла без кодировки и прочих уточнений.
     */
    private function cleanMime(string $header): string
    {
        $mime = trim(explode(';', $header)[0]);

        return $mime !== '' ? $mime : 'audio/mpeg';
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->retry(2, 1500, throw: false)
            ->baseUrl($this->baseUrl);
    }

    private function guard(Response $response, string $path): void
    {
        if ($response->successful()) {
            return;
        }

        $body = $response->json();
        $message = $body['error'] ?? $body['message'] ?? $response->body();

        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        Log::warning('Студия: провайдер отказал в звуке', [
            'path' => $path,
            'status' => $response->status(),
            'message' => mb_substr((string) $message, 0, 500),
            'details' => mb_substr(json_encode(
                $body['details'] ?? $body,
                JSON_UNESCAPED_UNICODE,
            ) ?: '', 0, 500),
        ]);

        throw new GenerationFailed(match ($response->status()) {
            401, 403 => 'Доступ к генерации звука закрыт провайдером.',
            402 => 'На счёте провайдера закончились средства.',
            429 => 'Слишком много запросов к провайдеру.',
            default => 'Провайдер не смог выполнить запрос.',
        });
    }
}
