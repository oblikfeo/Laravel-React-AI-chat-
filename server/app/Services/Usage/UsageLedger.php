<?php

namespace App\Services\Usage;

use App\Models\Chat;
use App\Models\Generation;
use App\Models\Guest;
use App\Models\Message;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Ai\AiResponse;
use App\Services\Ai\ModelCatalog;

/**
 * Записывает расходы в журнал.
 *
 * Здесь решается, сколько стоило обращение к провайдеру и на чей
 * счёт оно идёт: пользователя, гостя или сервиса.
 */
class UsageLedger
{
    /** Ответ текстовой модели в чате. */
    public function chat(Chat $chat, Message $reply, string $modelKey, AiResponse $response): void
    {
        $this->write([
            'user_id' => $chat->user_id,
            'guest_id' => $chat->user_id ? null : $chat->guest_id,
            'category' => UsageRecord::CATEGORY_CHAT,
            'model_key' => $modelKey,
            'provider_model' => $response->model,
            'cost' => self::textCost($response, ModelCatalog::price($modelKey)),
            'meta' => self::tokens($response),
            'message_id' => $reply->id,
        ]);
    }

    /** Решение диспетчера по сообщению: маленькая, но настоящая трата. */
    public function routing(Chat $chat, Message $question, float $cost, ?string $providerModel): void
    {
        if ($cost <= 0) {
            return;
        }

        $this->write([
            'user_id' => $chat->user_id,
            'guest_id' => $chat->user_id ? null : $chat->guest_id,
            'category' => UsageRecord::CATEGORY_CHAT,
            'model_key' => 'router',
            'provider_model' => $providerModel,
            'cost' => $cost,
            'message_id' => $question->id,
        ]);
    }

    /**
     * Работа Студии. Списывается сразу при запуске: пока провайдер
     * считает, бюджет уже занят, иначе можно запустить десяток
     * роликов, пока первый не готов.
     *
     * @param  array{scale?: int}  $options
     */
    public function generation(Generation $generation, array $options = []): void
    {
        $this->write([
            'user_id' => $generation->user_id,
            'guest_id' => $generation->user_id ? null : $generation->guest_id,
            'category' => self::categoryOf($generation),
            'model_key' => $generation->model_key,
            'provider_model' => self::providerModelOf($generation),
            'cost' => GenerationCost::of($generation, $options),
            'meta' => array_filter([
                'operation' => $generation->operation,
                'variants' => $generation->variants > 1 ? (int) $generation->variants : null,
                'seconds' => $generation->duration ? (int) $generation->duration : null,
                'resolution' => $generation->resolution,
            ]),
            'generation_id' => $generation->id,
        ]);
    }

    /** Работа не получилась — человек за неё не платит. */
    public function refund(Generation $generation): void
    {
        UsageRecord::query()->where('generation_id', $generation->id)->delete();
    }

    /**
     * Служебное обращение к текстовой модели: перевод описания,
     * текст персонажа. Без владельца — расход сервиса.
     */
    public function service(AiResponse $response, string $model, ?User $user = null): void
    {
        $this->write([
            'user_id' => $user?->id,
            'category' => UsageRecord::CATEGORY_SERVICE,
            'model_key' => ModelCatalog::has($model) ? $model : null,
            'provider_model' => $response->model ?? $model,
            'cost' => self::textCost($response, ModelCatalog::price($model)),
            'meta' => self::tokens($response),
        ]);
    }

    /** Голосовой ввод: цена за секунду записи. */
    public function voice(?User $user, ?Guest $guest, int $seconds): void
    {
        $this->write([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'category' => UsageRecord::CATEGORY_VOICE,
            'model_key' => 'transcription',
            'provider_model' => config('ai.transcription.model'),
            'cost' => $seconds * (float) config('ai.transcription.cost_per_second', 0),
            'meta' => ['seconds' => $seconds],
        ]);
    }

    /**
     * Цена ответа: названная провайдером, иначе по токенам и прайсу.
     *
     * @param  array{input: float, output: float}|null  $price  за миллион токенов
     */
    public static function textCost(AiResponse $response, ?array $price): float
    {
        if ($response->cost !== null) {
            return $response->cost;
        }

        if (! $price) {
            return 0.0;
        }

        return ($response->inputTokens * $price['input'] + $response->outputTokens * $price['output']) / 1_000_000;
    }

    /** @return array<string, int> */
    private static function tokens(AiResponse $response): array
    {
        return array_filter([
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
        ]);
    }

    private static function categoryOf(Generation $generation): string
    {
        return match (true) {
            $generation->kind === Generation::KIND_VIDEO => UsageRecord::CATEGORY_VIDEO,
            $generation->kind === Generation::KIND_AUDIO => UsageRecord::CATEGORY_AUDIO,
            $generation->operation === Generation::OP_GENERATE => UsageRecord::CATEGORY_IMAGE,
            default => UsageRecord::CATEGORY_EDIT,
        };
    }

    private static function providerModelOf(Generation $generation): ?string
    {
        return match (true) {
            $generation->kind === Generation::KIND_VIDEO => config("studio.video.models.{$generation->model_key}.provider_model"),
            $generation->operation === Generation::OP_SPEECH => config("studio.speech.models.{$generation->model_key}.provider_model"),
            $generation->operation === Generation::OP_MUSIC => config("studio.music.models.{$generation->model_key}.provider_model"),
            $generation->operation === Generation::OP_EFFECT => config("studio.effects.models.{$generation->model_key}.provider_model"),
            $generation->operation === Generation::OP_VOICE_CHANGE => config('studio.voice_changer.provider_model'),
            $generation->operation === Generation::OP_GENERATE => config("studio.models.{$generation->model_key}.provider_model"),
            $generation->operation === Generation::OP_UPSCALE => 'upscale',
            $generation->operation === Generation::OP_BACKGROUND => 'background-remove',
            default => config('studio.edit.model'),
        };
    }

    /** @param  array<string, mixed>  $attributes */
    private function write(array $attributes): void
    {
        UsageRecord::create([
            ...$attributes,
            'cost' => round(max(0.0, (float) $attributes['cost']), 6),
        ]);
    }
}
