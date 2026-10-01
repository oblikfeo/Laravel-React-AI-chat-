<?php

namespace App\Services\Studio\Venice;

use App\Services\Studio\GenerationFailed;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Обращения к Venice AI.
 *
 * Знает только про протокол: какой адрес, что отправить, как разобрать
 * ответ. Что именно рисовать, решают инструменты Студии.
 */
class VeniceClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 180,
    ) {
    }

    /**
     * Картинки приходят набором: моделей, умеющих несколько вариантов
     * за раз, у провайдера хватает.
     *
     * @return array<int, string> содержимое файлов
     */
    public function images(string $path, array $payload): array
    {
        $data = $this->post($path, $payload);

        $images = $data['images'] ?? [];

        if (! $images) {
            throw new GenerationFailed('Провайдер не вернул изображение.');
        }

        return array_map(
            static fn (string $base64): string => base64_decode($base64, true) ?: '',
            $images,
        );
    }

    /**
     * Инструменты правки отдают готовый файл, а не JSON.
     *
     * Провайдер может ответить с кодом 200 и объяснением в JSON —
     * например, когда цензура не пропустила запрос. Такой ответ
     * файлом не является, и сохранять его нельзя.
     */
    public function binary(string $path, array $payload): string
    {
        $response = $this->request()->post($path, $payload);

        $this->guard($response, $path);

        $body = $response->body();

        if (str_starts_with($body, '{')) {
            Log::warning('Студия: вместо файла пришёл ответ провайдера', [
                'path' => $path,
                'body' => mb_substr($body, 0, 300),
            ]);

            throw new GenerationFailed('Провайдер не вернул файл.');
        }

        return $body;
    }

    public function get(string $path, array $query = []): array
    {
        $response = $this->request()->get($path, $query);

        $this->guard($response, $path);

        return $response->json() ?? [];
    }

    public function post(string $path, array $payload): array
    {
        $response = $this->request()->post($path, $payload);

        $this->guard($response, $path);

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout($this->timeout)
            // Провайдер иногда отвечает не с первой попытки, а ждать
            // человеку всё равно: пробуем дважды, прежде чем сдаться.
            ->retry(2, 1500, throw: false)
            ->baseUrl($this->baseUrl);
    }

    /**
     * Разбор неудачи.
     *
     * Текст провайдера в интерфейс не попадает: он на английском и про
     * внутренности. Пользователю причину подбирает вызывающий код.
     */
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

        Log::warning('Студия: провайдер отказал', [
            'path' => $path,
            'status' => $response->status(),
            'message' => mb_substr((string) $message, 0, 500),
        ]);

        throw new GenerationFailed(
            match ($response->status()) {
                401, 403 => 'Доступ к генерации закрыт провайдером.',
                402 => 'На счёте провайдера закончились средства.',
                429 => 'Слишком много запросов к провайдеру.',
                default => 'Провайдер не смог выполнить запрос.',
            },
        );
    }
}
