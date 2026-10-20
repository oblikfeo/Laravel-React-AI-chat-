<?php

namespace App\Actions\Chat;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\ModelCatalog;
use App\Services\Characters\CharacterPrompt;
use App\Services\Chat\ChatImages;
use App\Services\Chat\Routing\ChatRouter;
use App\Services\Chat\Routing\Route;
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
    public function __construct(
        private readonly AiChatProvider $provider,
        private readonly CharacterPrompt $characterPrompt,
        private readonly ChatRouter $router,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
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

        // Что человек хочет, решаем сразу: пока ждём ответа, чат
        // показывает анимацию того, что делается.
        $route = $this->router->decide($chat, $message->load('attachments'));

        $message->forceFill([
            'routing' => $route->toArray(),
            'intent' => $route->toStudio($content)['mode'] ?? null,
        ])->save();

        $chat->forceFill(['last_message_at' => now()])->save();

        return $message;
    }

    /**
     * Отвечает на последнее сообщение.
     *
     * Просьбу нарисовать или поправить картинку выполняют модели
     * Студии, всё остальное — текстовая модель чата.
     */
    public function reply(Chat $chat): Message
    {
        $question = $this->question($chat);
        $route = $question ? $this->routeOf($chat, $question) : Route::chat();

        $reply = ($question ? $this->replyWithStudio($chat, $question, $route) : null)
            ?? $this->askProvider($chat, $this->chatModelFor($chat, $route));

        // Страховка: если модель всё же изобразила картинку текстом,
        // делаем настоящую.
        if ($question && $this->pretendsToDraw($reply)) {
            $reply->delete();

            $reply = $this->replyWithStudio($chat, $question, new Route('image', $question->content, source: 'guard'));
        }

        $chat->forceFill(['last_message_at' => now()])->save();

        return $reply;
    }

    private function question(Chat $chat): ?Message
    {
        return $chat->messages()
            ->with('attachments')
            ->where('role', Message::ROLE_USER)
            ->latest('id')
            ->first();
    }

    /** Решение по сообщению: принятое при сохранении или новое. */
    private function routeOf(Chat $chat, Message $question): Route
    {
        return $question->routing
            ? Route::fromArray($question->routing)
            : $this->router->decide($chat, $question);
    }

    /**
     * Ответ инструментом Студии, если человек просит то, что она делает.
     *
     * Картинку чат рисует или правит сам, на звук и видео отвечает
     * карточкой со ссылкой в Студию.
     */
    private function replyWithStudio(Chat $chat, Message $question, Route $route): ?Message
    {
        $studio = $route->toStudio($question->content);

        if (! $studio) {
            return null;
        }

        $images = ChatImages::for($chat, $question);

        return in_array($studio['mode'], ['image', 'edit'], true)
            ? app(ReplyWithImage::class)->handle($chat, $studio, $images->contents())
            : app(ReplyWithStudioLink::class)->handle($chat, $studio, $images);
    }

    /**
     * Какую текстовую модель взять.
     *
     * Подбираем, только если человек оставил выбор за нами (Auto):
     * явный выбор модели не трогаем. Гостю и персонажу модель задана.
     */
    private function chatModelFor(Chat $chat, Route $route): ?string
    {
        $suggested = $route->chatModel;

        if (! $suggested || $suggested === 'auto' || ! ModelCatalog::has($suggested)) {
            return null;
        }

        return $chat->model_key === 'auto' && $chat->user_id && ! $chat->character_id
            ? $suggested
            : null;
    }

    /** Ответ, в котором модель изображает картинку словами. */
    private function pretendsToDraw(Message $reply): bool
    {
        return $reply->generation_id === null
            && preg_match('/^\s*[\[(](image|picture|sent a picture|изображени|картинк)/iu', $reply->content) === 1;
    }

    private function askProvider(Chat $chat, ?string $model = null): Message
    {
        // В диалоге с персонажем модель задаёт его автор.
        $key = ModelCatalog::resolve($model ?? $chat->effectiveModelKey());
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
            $this->optionsFor($chat),
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
            ->with(['attachments', 'generation'])
            // Отметки о смене модели — для человека, не для модели.
            ->whereIn('role', [Message::ROLE_USER, Message::ROLE_ASSISTANT])
            ->latest('id')
            ->limit(config('ai.context_messages'))
            ->get()
            ->reverse()
            ->values();

        // Ответы картинкой в историю не попадают: увидев образец, модель
        // начинает «рисовать» текстом. О нарисованном говорим в
        // системном запросе.
        $pictures = $history
            ->filter(fn (Message $message) => $message->generation)
            ->map(fn (Message $message) => '«'.$message->generation->prompt.'»')
            ->implode(', ');

        $turns = $this->merged($history
            ->reject(fn (Message $message) => $message->generation)
            ->map(fn (Message $message) => $this->formatMessage($message))
            ->values()
            ->all());

        $system = $this->systemPrompt($chat);

        if ($pictures !== '') {
            $system .= "\n\nPictures the app has already shown the user in this conversation: {$pictures}.";
        }

        return array_merge([['role' => 'system', 'content' => $system]], $turns);
    }

    /**
     * Кем быть модели в этом чате.
     *
     * В обычном чате это наш помощник, в диалоге с персонажем —
     * сам персонаж со всем, что о нём написал автор.
     */
    private function systemPrompt(Chat $chat): string
    {
        $role = $chat->character
            ? $this->characterPrompt->for($chat->character)
            : config('ai.system_prompt');

        return $role."\n\n".config('ai.images_note');
    }

    /**
     * Тонкие настройки ответа.
     *
     * @return array<string, mixed>
     */
    private function optionsFor(Chat $chat): array
    {
        $temperature = $chat->character?->temperature;

        return $temperature !== null ? ['temperature' => $temperature] : [];
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
     * Соседние реплики одной стороны склеиваются в одну.
     *
     * Так бывает, когда ответ картинкой выпал из истории: две реплики
     * человека подряд некоторые модели не принимают.
     *
     * @param  array<int, array<string, mixed>>  $turns
     * @return array<int, array<string, mixed>>
     */
    private function merged(array $turns): array
    {
        $result = [];

        foreach ($turns as $turn) {
            $last = array_key_last($result);

            if ($last !== null
                && $result[$last]['role'] === $turn['role']
                && is_string($result[$last]['content'])
                && is_string($turn['content'])) {
                $result[$last]['content'] .= "\n\n".$turn['content'];

                continue;
            }

            $result[] = $turn;
        }

        return $result;
    }

    /**
     * Заголовок чата из первого сообщения пользователя.
     */
    public static function titleFrom(string $content): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $content)), 48, '…');
    }
}
