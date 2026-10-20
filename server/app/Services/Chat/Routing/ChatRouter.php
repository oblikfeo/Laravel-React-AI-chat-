<?php

namespace App\Services\Chat\Routing;

use App\Models\Chat;
use App\Models\Message;
use App\Services\Chat\ChatImages;
use App\Services\Chat\StudioIntent;

/**
 * Решает, куда отдать сообщение: текстовой модели или инструменту.
 *
 * Решает модель-диспетчер. Если она не ответила, решает запасное
 * правило по словам — чат не должен ломаться из-за диспетчера.
 * Решение проверяется: инструмент правки без картинки невозможен,
 * и такую ошибку диспетчера чат не повторяет.
 */
final class ChatRouter
{
    /** Сколько прошлых реплик видит диспетчер. */
    private const RECENT = 4;

    /** Операции, которым нужна картинка. */
    private const NEED_PICTURE = ['edit', 'combine', 'upscale', 'remove_background', 'animate'];

    public function __construct(
        private readonly RequestRouter $router,
        private readonly StudioIntent $rules,
    ) {}

    public function decide(Chat $chat, Message $question): Route
    {
        $images = ChatImages::for($chat, $question);
        $input = $this->input($chat, $question, $images);

        $route = $this->router->route($input)
            ?? Route::fromRules($this->rules->detect($question->content, $images->count()));

        return $this->checked($route, $input);
    }

    private function input(Chat $chat, Message $question, ChatImages $images): RoutingInput
    {
        $earlier = $chat->messages()
            ->with('generation')
            ->whereIn('role', [Message::ROLE_USER, Message::ROLE_ASSISTANT])
            ->where('id', '<', $question->id)
            ->latest('id')
            ->limit(self::RECENT)
            ->get()
            ->reverse()
            ->values();

        $previous = $earlier->last();

        return new RoutingInput(
            message: $question->content,
            attachedImages: $images->attached(),
            previousReplyWasImage: $previous?->role === Message::ROLE_ASSISTANT && $previous->generation?->isReady(),
            recent: $earlier->map(fn (Message $message) => [
                'role' => $message->role,
                'text' => $message->generation
                    ? '(sent a picture: '.$message->generation->prompt.')'
                    : mb_substr($message->content, 0, 300),
            ])->all(),
        );
    }

    /**
     * Решение, которое можно выполнить.
     *
     * Без картинки править нечего, объединять — нужно две. Неуверенное
     * решение не запускает инструмент: обычный ответ ничего не стоит
     * человеку, а ненужная картинка — стоит.
     */
    private function checked(Route $route, RoutingInput $input): Route
    {
        if (! $route->isTool()) {
            return $route;
        }

        if ($route->confidence < (float) config('ai.router.min_confidence')) {
            return Route::chat(source: $route->source);
        }

        if (in_array($route->intent, self::NEED_PICTURE, true) && $input->images() === 0) {
            return Route::chat(source: $route->source);
        }

        if ($route->intent === 'combine' && $input->attachedImages < 2) {
            return new Route('edit', $route->prompt, $route->confidence, source: $route->source);
        }

        return $route;
    }
}
