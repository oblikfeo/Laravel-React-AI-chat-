<?php

namespace App\Actions\Chat;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\ModelCatalog;
use Illuminate\Http\UploadedFile;
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

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    public function handle(Chat $chat, string $content, array $files = []): Message
    {
        $message = $this->store($chat, $content, $files);

        $this->reply($chat);

        return $message;
    }

    /**
     * Сохраняет сообщение пользователя, не обращаясь к модели.
     *
     * Отделено от ответа, чтобы создание чата не ждало провайдера:
     * человек попадает в диалог сразу и ждёт уже там.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function store(Chat $chat, string $content, array $files = []): Message
    {
        $message = $chat->messages()->create([
            'role' => Message::ROLE_USER,
            'content' => $content,
        ]);

        foreach ($files as $file) {
            app(StoreAttachment::class)->handle($message, $file);
        }

        $chat->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    /** Запрашивает ответ модели и сохраняет его. */
    public function reply(Chat $chat): Message
    {
        $reply = $this->askProvider($chat);

        $chat->forceFill(['last_message_at' => now()])->save();

        return $reply;
    }

    private function askProvider(Chat $chat): Message
    {
        $key = ModelCatalog::resolve($chat->model_key);
        $messages = $this->buildContext($chat);

        // Картинку понимает не каждая модель: при вложении подставляем
        // ту, которая умеет, иначе ответ будет про неумение читать файлы.
        $hasImages = $this->hasImages($chat);
        $key = ModelCatalog::forRequest($key, $hasImages);

        try {
            return $this->complete($chat, $messages, $key);
        } catch (Throwable $exception) {
            Log::warning('Модель не ответила, пробуем запасную', [
                'chat_id' => $chat->id,
                'model' => $key,
                'message' => $exception->getMessage(),
            ]);

            // У бесплатных моделей строгий лимит частоты. Молча берём
            // запасную: человеку важен ответ, а не наши ограничения.
            $fallback = ModelCatalog::fallback($key);

            if ($fallback) {
                try {
                    return $this->complete($chat, $messages, $fallback);
                } catch (Throwable $second) {
                    $exception = $second;
                }
            }

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

    private function complete(Chat $chat, array $messages, string $key): Message
    {
        $response = $this->provider->complete(
            $messages,
            ModelCatalog::providerModel($key),
        );

        return $chat->messages()->create([
            'role' => Message::ROLE_ASSISTANT,
            'content' => $response->content,
            'model' => $response->model,
        ]);
    }

    private function hasImages(Chat $chat): bool
    {
        return $chat->messages()
            ->whereHas('attachments', fn ($q) => $q->where('mime', 'like', 'image/%'))
            ->exists();
    }

    /**
     * Собирает историю для модели: системный промпт плюс последние сообщения.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildContext(Chat $chat): array
    {
        $history = $chat->messages()
            ->with('attachments')
            // Отметки о смене модели — для человека, не для модели.
            ->whereIn('role', [Message::ROLE_USER, Message::ROLE_ASSISTANT])
            ->latest('id')
            ->limit(config('ai.context_messages'))
            ->get()
            ->reverse()
            ->map(fn (Message $message) => $this->formatMessage($message))
            ->values()
            ->all();

        return array_merge(
            [['role' => 'system', 'content' => config('ai.system_prompt')]],
            $history,
        );
    }

    /**
     * Готовит одно сообщение к отправке.
     *
     * Текст документа вставляется прямо в сообщение: модель не умеет
     * открывать файлы, она работает только с тем, что ей прислали.
     * Картинки уходят отдельными частями в формате провайдера.
     *
     * @return array<string, mixed>
     */
    private function formatMessage(Message $message): array
    {
        $attachments = $message->attachments;

        if ($attachments->isEmpty()) {
            return ['role' => $message->role, 'content' => $message->content];
        }

        $text = $message->content;

        foreach ($attachments->where('extracted_text', '!=', null) as $document) {
            $text .= "\n\n--- {$document->name} ---\n{$document->extracted_text}";
        }

        $images = $attachments->filter(fn (Attachment $a) => $a->isImage());

        if ($images->isEmpty()) {
            return ['role' => $message->role, 'content' => $text];
        }

        $parts = [['type' => 'text', 'text' => $text]];

        foreach ($images as $image) {
            $parts[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $image->asDataUrl()],
            ];
        }

        return ['role' => $message->role, 'content' => $parts];
    }

    /**
     * Заголовок чата из первого сообщения пользователя.
     */
    public static function titleFrom(string $content): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $content)), 48, '…');
    }
}
