<?php

namespace App\Actions\Chat;

use App\Models\Chat;
use App\Models\Message;
use App\Services\Ai\AiChatProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Записывает сообщение пользователя, запрашивает ответ модели
 * и сохраняет его в том же чате.
 *
 * Ошибка провайдера не роняет диалог: вместо ответа сохраняется
 * поясняющее сообщение, чтобы пользователь не потерял свой текст.
 */
class SendMessage
{
    public function __construct(private readonly AiChatProvider $provider)
    {
    }

    public function handle(Chat $chat, string $content): Message
    {
        $chat->messages()->create([
            'role' => Message::ROLE_USER,
            'content' => $content,
        ]);

        $chat->forceFill(['last_message_at' => now()])->save();

        $reply = $this->askProvider($chat);

        $chat->forceFill(['last_message_at' => now()])->save();

        return $reply;
    }

    private function askProvider(Chat $chat): Message
    {
        try {
            $response = $this->provider->complete($this->buildContext($chat));

            return $chat->messages()->create([
                'role' => Message::ROLE_ASSISTANT,
                'content' => $response->content,
                'model' => $response->model,
            ]);
        } catch (Throwable $exception) {
            Log::error('Запрос к AI-провайдеру не удался', [
                'chat_id' => $chat->id,
                'message' => $exception->getMessage(),
            ]);

            // Причина сбоя ушла в лог. Пользователю показываем короткое
            // сообщение без технических подробностей.
            return $chat->messages()->create([
                'role' => Message::ROLE_ASSISTANT,
                'content' => "I'm having trouble responding right now. Please try again in a moment.",
                'model' => null,
            ]);
        }
    }

    /**
     * Собирает историю для модели: системный промпт плюс последние сообщения.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function buildContext(Chat $chat): array
    {
        $history = $chat->messages()
            ->latest('id')
            ->limit(config('ai.context_messages'))
            ->get()
            ->reverse()
            ->map(fn (Message $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();

        return array_merge(
            [['role' => 'system', 'content' => config('ai.system_prompt')]],
            $history,
        );
    }

    /**
     * Заголовок чата из первого сообщения пользователя.
     */
    public static function titleFrom(string $content): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $content)), 48, '…');
    }
}
