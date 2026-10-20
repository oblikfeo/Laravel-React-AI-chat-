<?php

namespace App\Services\Chat\Routing;

/**
 * Что знает диспетчер о сообщении.
 */
final class RoutingInput
{
    /**
     * @param  array<int, array{role: string, text: string}>  $recent  последние реплики до сообщения
     */
    public function __construct(
        public readonly string $message,
        /** Сколько картинок приложено к самому сообщению. */
        public readonly int $attachedImages = 0,
        /** Был ли прошлый ответ чата картинкой. */
        public readonly bool $previousReplyWasImage = false,
        public readonly array $recent = [],
    ) {}

    /** Сколько картинок можно взять в работу. */
    public function images(): int
    {
        return $this->attachedImages ?: ($this->previousReplyWasImage ? 1 : 0);
    }
}
