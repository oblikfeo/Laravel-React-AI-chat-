<?php

namespace App\Actions\Chat;

use App\Actions\Studio\CreateGeneration;
use App\Actions\Studio\EditImage;
use App\Models\Chat;
use App\Models\Generation;
use App\Models\Message;
use App\Services\Chat\StudioIntent;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioLimiter;

/**
 * Отвечает в чате картинкой.
 *
 * Рисует и правит теми же моделями и по тем же правилам, что Студия:
 * дневной лимит общий, работа сохраняется в галерее. В ленту картинка
 * из чата не попадает — человек делал её для себя.
 *
 * Ответ — сама картинка, без слов. Текст появляется, только если
 * что-то не получилось.
 */
class ReplyWithImage
{
    public function __construct(
        private readonly CreateGeneration $create,
        private readonly EditImage $edit,
        private readonly StudioLimiter $limiter,
        private readonly ImageGenerator $generator,
    ) {}

    /**
     * @param  array{mode: string, operation: string, prompt: string}  $intent
     * @param  string|null  $source  содержимое картинки, которую правим
     */
    public function handle(Chat $chat, array $intent, ?string $source): Message
    {
        $user = $chat->user;
        $guest = $user ? null : $chat->guest;

        if (! $this->generator->isAvailable()) {
            return $this->say($chat, 'Image generation is not available right now. Please try again later.');
        }

        if (! $this->limiter->allows($user, $guest)) {
            return $this->say($chat, $user
                ? "You've reached today's image limit. It resets tomorrow."
                : "You've reached today's free image limit. Sign up to create more.");
        }

        $generation = $intent['mode'] === StudioIntent::EDIT && $source !== null
            ? $this->edit->handle($user, $guest, [$source], [
                'operation' => $intent['operation'],
                'prompt' => $intent['operation'] === Generation::OP_EDIT ? $intent['prompt'] : null,
                'model' => $this->modelFor($chat),
                'is_public' => false,
            ])
            : $this->create->handle($user, $guest, [
                'prompt' => $intent['prompt'],
                'model' => $this->modelFor($chat),
                'aspect_ratio' => '1:1',
                'variants' => 1,
                'is_public' => false,
            ])[0];

        if (! $generation->isReady()) {
            return $this->say($chat, $generation->failureNote() ?? 'Could not create this image. Please try again.');
        }

        return $chat->messages()->create([
            'role' => Message::ROLE_ASSISTANT,
            'content' => '',
            'generation_id' => $generation->id,
        ]);
    }

    /**
     * Какой моделью рисовать.
     *
     * В чате без цензуры и картинка без цензуры: человек выбрал это
     * для всего диалога. Такие работы в ленту не попадают и так.
     */
    private function modelFor(Chat $chat): string
    {
        return $chat->effectiveModelKey() === 'uncensored' && config('studio.models.uncensored')
            ? 'uncensored'
            : config('studio.default_model');
    }

    private function say(Chat $chat, string $text): Message
    {
        return $chat->messages()->create([
            'role' => Message::ROLE_ASSISTANT,
            'content' => $text,
            'model' => 'studio',
        ]);
    }
}
