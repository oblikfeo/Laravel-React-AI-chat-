<?php

namespace App\Services\Studio\Venice;

use App\Services\Studio\GenerationFailed;
use App\Services\Studio\QueuedJob;
use App\Services\Studio\QueueResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Долгие задачи у Venice.
 *
 * Музыка, эффекты, смена голоса и видео считаются дольше, чем человек
 * готов ждать ответа страницы, поэтому провайдер принимает запрос в
 * очередь и отдаёт номер задачи. Результат забирается отдельно.
 */
class VeniceQueue
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
        $response = $this->send(fn () => $this->request()
            ->attach('file', $recording, $filename)
            ->post('/audio/voice-changer/queue', $payload));

        $this->guard($response, '/audio/voice-changer/queue');

        return $this->jobFrom($response->json() ?? []);
    }

    /**
     * Ставит ролик в очередь.
     */
    public function queueVideo(array $payload): QueuedJob
    {
        return $this->enqueue('/video/queue', $payload);
    }

    /**
     * Спрашивает готовность ролика.
     *
     * Обычно готовый ролик приходит самим файлом. Но часть моделей
     * отвечает только состоянием, а файл лежит по ссылке, которую
     * провайдер назвал ещё при постановке задачи.
     */
    public function retrieveVideo(
        string $queueId,
        string $providerModel,
        ?string $downloadUrl = null,
    ): QueueResult {
        $response = $this->send(fn () => $this->request()->asJson()->post('/video/retrieve', [
            'queue_id' => $queueId,
            'model' => $providerModel,
        ]));

        $this->guard($response, '/video/retrieve');

        $type = $response->header('content-type');

        if (! str_contains($type, 'application/json')) {
            return QueueResult::ready(
                contents: $response->body(),
                mime: $this->cleanMime($type, 'video/mp4'),
            );
        }

        $body = $response->json() ?? [];
        $status = $body['status'] ?? 'PROCESSING';

        if ($status === 'COMPLETED' && $downloadUrl) {
            return $this->download($downloadUrl);
        }

        return QueueResult::pending(
            status: $status,
            expectedMs: isset($body['average_execution_time'])
                ? (int) $body['average_execution_time']
                : null,
        );
    }

    /**
     * Спрашивает готовность звука.
     *
     * Пока задача считается, провайдер отвечает состоянием; когда
     * готова — самим файлом.
     */
    public function retrieve(
        string $queueId,
        string $providerModel,
        bool $voiceChange = false,
    ): QueueResult {
        $path = $voiceChange
            ? '/audio/voice-changer/retrieve'
            : '/audio/retrieve';

        // Это POST с телом, а не запрос с параметром в адресе, и
        // провайдеру нужна ещё модель, которой задача считается.
        $response = $this->send(fn () => $this->request()->asJson()->post($path, [
            'queue_id' => $queueId,
            'model' => $providerModel,
        ]));

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
        $response = $this->send(fn () => $this->request()->asJson()->post($path, $payload));

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
            downloadUrl: $body['download_url'] ?? null,
        );
    }

    /**
     * Забирает готовый файл по ссылке провайдера.
     *
     * Ссылка уже подписана, поэтому ключ с ней не отправляем.
     */
    private function download(string $url): QueueResult
    {
        $response = $this->send(
            fn () => Http::timeout($this->timeout)->retry(2, 1500, throw: false)->get($url),
        );

        if (! $response->successful()) {
            Log::warning('Студия: готовый файл не скачался', [
                'status' => $response->status(),
            ]);

            throw GenerationFailed::temporary('Готовый файл пока не скачался.');
        }

        return QueueResult::ready(
            contents: $response->body(),
            mime: $this->cleanMime($response->header('content-type'), 'video/mp4'),
        );
    }

    /**
     * Тип файла без кодировки и прочих уточнений.
     */
    private function cleanMime(string $header, string $fallback = 'audio/mpeg'): string
    {
        $mime = trim(explode(';', $header)[0]);

        return $mime !== '' ? $mime : $fallback;
    }

    /**
     * Выполняет запрос и переводит обрыв связи в понятный сбой.
     *
     * Без этого недоступность провайдера по сети роняла бы страницу
     * целиком, а уже считающаяся работа пропадала бы.
     *
     * @param  callable(): Response  $call
     */
    private function send(callable $call): Response
    {
        try {
            return $call();
        } catch (ConnectionException $exception) {
            Log::warning('Студия: нет связи с провайдером', [
                'message' => mb_substr($exception->getMessage(), 0, 300),
            ]);

            throw GenerationFailed::temporary('Нет связи с провайдером.');
        }
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

        Log::warning('Студия: провайдер отказал в долгой задаче', [
            'path' => $path,
            'status' => $response->status(),
            'message' => mb_substr((string) $message, 0, 500),
            'details' => mb_substr(json_encode(
                $body['details'] ?? $body,
                JSON_UNESCAPED_UNICODE,
            ) ?: '', 0, 500),
        ]);

        $reason = match ($response->status()) {
            401, 403 => 'Доступ к генерации закрыт провайдером.',
            402 => 'На счёте провайдера закончились средства.',
            422 => 'Провайдер отклонил запрос по своим правилам.',
            429 => 'Слишком много запросов к провайдеру.',
            default => 'Провайдер не смог выполнить запрос.',
        };

        // Перегрузка и сбои сервера проходят сами: задачу, которая
        // уже считается, из-за них бросать нельзя.
        throw $response->status() === 429 || $response->serverError()
            ? GenerationFailed::temporary($reason)
            : new GenerationFailed($reason);
    }
}
