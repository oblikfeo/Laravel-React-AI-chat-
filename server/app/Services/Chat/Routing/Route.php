<?php

namespace App\Services\Chat\Routing;

use App\Models\Generation;
use App\Services\Chat\StudioIntent;

/**
 * Куда отдать сообщение чата.
 *
 * intent — что человек хочет: обычный ответ (chat), картинку, правку
 * картинки, звук или видео. prompt — описание для инструмента Студии.
 * chatModel — какая текстовая модель лучше подходит, если человек
 * оставил выбор за нами (Auto).
 */
final class Route
{
    public const CHAT = 'chat';

    /** Что умеет чат и куда это ведёт: раздел и операция Студии. */
    public const TOOLS = [
        'image' => [StudioIntent::IMAGE, Generation::OP_GENERATE],
        'edit' => [StudioIntent::EDIT, Generation::OP_EDIT],
        'combine' => [StudioIntent::EDIT, Generation::OP_COMBINE],
        'upscale' => [StudioIntent::EDIT, Generation::OP_UPSCALE],
        'remove_background' => [StudioIntent::EDIT, Generation::OP_BACKGROUND],
        'music' => [StudioIntent::AUDIO, StudioIntent::AUDIO_MUSIC],
        'speech' => [StudioIntent::AUDIO, StudioIntent::AUDIO_SPEECH],
        'sound_effect' => [StudioIntent::AUDIO, StudioIntent::AUDIO_EFFECT],
        'video' => [StudioIntent::VIDEO, Generation::OP_VIDEO],
        'animate' => [StudioIntent::VIDEO, Generation::OP_ANIMATE],
    ];

    public const CHAT_MODELS = ['auto', 'fast', 'smart', 'uncensored', 'roleplay'];

    public function __construct(
        public readonly string $intent,
        public readonly string $prompt = '',
        public readonly float $confidence = 1.0,
        public readonly ?string $chatModel = null,
        /** router — решила модель-диспетчер, rules — запасное правило. */
        public readonly string $source = 'router',
    ) {}

    public static function chat(?string $chatModel = null, string $source = 'router'): self
    {
        return new self(self::CHAT, chatModel: $chatModel, source: $source);
    }

    /**
     * Решение запасного правила в том же виде.
     *
     * @param  array{mode: string, operation: string, prompt: string}|null  $intent
     */
    public static function fromRules(?array $intent): self
    {
        if (! $intent) {
            return self::chat(source: 'rules');
        }

        foreach (self::TOOLS as $name => [$mode, $operation]) {
            if ($mode === $intent['mode'] && $operation === $intent['operation']) {
                return new self($name, $intent['prompt'], 1.0, source: 'rules');
            }
        }

        return self::chat(source: 'rules');
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            intent: (string) ($data['intent'] ?? self::CHAT),
            prompt: (string) ($data['prompt'] ?? ''),
            confidence: (float) ($data['confidence'] ?? 1.0),
            chatModel: $data['chat_model'] ?? null,
            source: (string) ($data['source'] ?? 'router'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'intent' => $this->intent,
            'prompt' => $this->prompt,
            'confidence' => $this->confidence,
            'chat_model' => $this->chatModel,
            'source' => $this->source,
        ];
    }

    public function isTool(): bool
    {
        return isset(self::TOOLS[$this->intent]);
    }

    /**
     * Задание для инструмента Студии.
     *
     * @return array{mode: string, operation: string, prompt: string}|null
     */
    public function toStudio(string $fallbackPrompt): ?array
    {
        if (! $this->isTool()) {
            return null;
        }

        [$mode, $operation] = self::TOOLS[$this->intent];

        return [
            'mode' => $mode,
            'operation' => $operation,
            'prompt' => mb_substr($this->prompt !== '' ? $this->prompt : $fallbackPrompt, 0, 2000),
        ];
    }
}
