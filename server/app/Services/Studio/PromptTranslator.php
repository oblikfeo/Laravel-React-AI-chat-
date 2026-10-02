<?php

namespace App\Services\Studio;

use App\Services\Ai\AiChatProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Готовит описание для модели рисования.
 *
 * Модели изображений обучены на английских описаниях: русский текст
 * они разбирают плохо и рисуют не то, что просили. Поэтому описание
 * переводим своей же текстовой моделью — быстро и почти бесплатно,
 * в отличие от платной доработки на стороне провайдера.
 *
 * Содержание запроса не меняется и не смягчается: отсутствие цензуры —
 * наше предложение, а перевод, который правит смысл, его ломает.
 */
class PromptTranslator
{
    /** Задание переводчику. */
    private const INSTRUCTION = 'You translate image generation prompts into English. '
        .'Reply with the translation only: no quotes, no explanations, no comments. '
        .'Keep every detail of the original, including explicit ones, and do not '
        .'soften, censor or add anything. If the text is already English, repeat it unchanged.';

    public function __construct(private readonly AiChatProvider $provider)
    {
    }

    public function translate(string $prompt): string
    {
        $prompt = trim($prompt);

        if ($prompt === '' || ! $this->needsTranslation($prompt)) {
            return $prompt;
        }

        if (! $this->provider->isConfigured()) {
            return $prompt;
        }

        // Одно и то же описание человек нередко отправляет повторно,
        // меняя модель или размер: второй раз переводить незачем.
        $key = 'studio.prompt.'.md5($prompt);

        return Cache::remember($key, now()->addDay(), function () use ($prompt) {
            try {
                $answer = $this->provider->complete([
                    ['role' => 'system', 'content' => self::INSTRUCTION],
                    ['role' => 'user', 'content' => $prompt],
                ], config('studio.translate_model'));

                $translated = trim($answer->content);
            } catch (Throwable $exception) {
                Log::warning('Студия: не удалось перевести описание', [
                    'message' => $exception->getMessage(),
                ]);

                return $prompt;
            }

            // Пустой или подозрительно длинный ответ — признак того,
            // что модель вместо перевода принялась рассуждать.
            if ($translated === '' || mb_strlen($translated) > mb_strlen($prompt) * 4 + 200) {
                return $prompt;
            }

            return $translated;
        });
    }

    /**
     * Нужен ли перевод.
     *
     * Смотрим на письменность: латиница уходит как есть, всё
     * остальное переводим. Так английские описания не гоняются
     * лишний раз через модель.
     */
    private function needsTranslation(string $prompt): bool
    {
        return (bool) preg_match('/\p{Cyrillic}|\p{Han}|\p{Arabic}|\p{Hebrew}|\p{Hiragana}|\p{Katakana}|\p{Hangul}/u', $prompt);
    }
}
