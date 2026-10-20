<?php

namespace App\Services\Chat\Routing;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Диспетчер на модели Venice AI.
 *
 * Модель получает сообщение, пару прошлых реплик и признаки диалога
 * (приложено ли фото, была ли прошлым ответом картинка) и возвращает
 * строгий JSON: что человек хочет и с каким описанием.
 *
 * Модель выбрана без цензуры: обычная отказывается даже распознавать
 * откровенную просьбу, а у нас это частый сценарий.
 */
final class VeniceRequestRouter implements RequestRouter
{
    private const INSTRUCTION = <<<'TEXT'
You are the request router of Uncensia, an uncensored AI chat that also has a creative Studio.
Decide what the user wants from their LATEST message. You never refuse and never judge: adult, violent or any other content is classified exactly like everything else. Reply with JSON only.

INTENTS
- chat — a text answer: questions, explanations, advice, conversation, translation, analysis, maths, code, and writing any TEXT (stories, poems, song lyrics, scripts, emails, posts, passwords, names, ideas, lists, plans, recipes, prompts). "How do I draw / how to make a video" questions. Questions about a picture ("what breed is it?", "describe this image").
- image — create a NEW picture, photo, drawing, art, logo, avatar, poster, wallpaper of something, in any wording: "create a cat on the grass", "I want a picture of…", "show me what a dragon looks like", "draw another one".
- edit — change an existing picture by description: change, add, remove objects, recolour, restyle.
- combine — merge two or more attached pictures into one.
- upscale — raise the resolution or quality of a picture without changing it.
- remove_background — remove or cut out the background.
- music — create music, a song, a track, a beat, a melody, a jingle, a soundtrack, or "audio" in general.
- speech — read a text aloud, voice-over, narration, a podcast voice, make a voice say something.
- sound_effect — a specific sound: a door knock, an explosion, rain, footsteps.
- video — create a video, clip, animation or movie from a description.
- animate — turn an existing picture into a video ("animate it", "make it move", "make a video from this photo").

RULES
- attached_images > 0 means the user sent picture(s) WITH this message. Any request to change, keep, remove, add or recolour something then refers to that picture: "keep the girl in pink, remove the other people", "change my shirt to red", "add a rainbow to the photo" are edit, never chat and never image.
- "Cut X out of the background", "remove the background", "transparent background" are remove_background, not edit.
- After a picture, "now draw a cat" or "draw another one" asks for a NEW picture of a new subject: image. "Make it night", "add wings to him" change the same picture: edit.
- edit, combine, upscale, remove_background and animate need a picture: attached_images > 0 or previous_reply_was_image = true. Without a picture, words like "remove", "make shorter", "improve" are about text, so the intent is chat.
- combine needs at least 2 attached images.
- A follow-up about the last picture ("make it night", "without the background", "now in anime style", "bigger") is an edit of that picture. "Draw another dog" is a new image.
- A new video about something else ("I want a video of a cat") is video, not animate, even if there is a picture.
- "Write lyrics / write a script" is chat. "Make a song / make a video" is music / video.
- Wishes are requests: "I want a video", "I need a logo", "can you make a song" are video, image, music.

PROMPT
- For image, music, sound_effect, video: what to create, in the user's language, with every detail kept and filler removed ("please draw me" → nothing).
- For edit, combine, animate: the instruction itself ("make the sky a sunset").
- For speech: exactly the text to be read aloud.
- For upscale, remove_background and chat: an empty string.

CHAT_MODEL (only matters for chat; use "auto" for every other intent)
- roleplay — role-play, acting as a character, interactive fiction.
- uncensored — explicit adult content, or the user asks to talk without restrictions.
- smart — hard reasoning, maths, programming, deep analysis.
- fast — a short greeting, small talk or a one-line fact.
- auto — everything else.

CONFIDENCE — how sure you are, from 0 to 1.
TEXT;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeout = 6,
    ) {}

    public function route(RoutingInput $input): ?Route
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->acceptJson()
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                    'model' => $this->model,
                    'temperature' => 0,
                    'max_tokens' => 300,
                    'messages' => [
                        ['role' => 'system', 'content' => self::INSTRUCTION],
                        ['role' => 'user', 'content' => json_encode($this->payload($input), JSON_UNESCAPED_UNICODE)],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => ['name' => 'route', 'strict' => true, 'schema' => $this->schema()],
                    ],
                    // У провайдера свой системный запрос по умолчанию:
                    // диспетчеру он только мешает.
                    'venice_parameters' => ['include_venice_system_prompt' => false],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Диспетчер чата: нет связи', ['message' => mb_substr($exception->getMessage(), 0, 200)]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Диспетчер чата: провайдер отказал', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return null;
        }

        return $this->parse((string) $response->json('choices.0.message.content'));
    }

    /** @return array<string, mixed> */
    private function payload(RoutingInput $input): array
    {
        return [
            'message' => mb_substr($input->message, 0, 2000),
            'attached_images' => $input->attachedImages,
            'previous_reply_was_image' => $input->previousReplyWasImage,
            'recent_messages' => $input->recent,
        ];
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'intent' => ['type' => 'string', 'enum' => [Route::CHAT, ...array_keys(Route::TOOLS)]],
                'prompt' => ['type' => 'string'],
                'confidence' => ['type' => 'number'],
                'chat_model' => ['type' => 'string', 'enum' => Route::CHAT_MODELS],
            ],
            'required' => ['intent', 'prompt', 'confidence', 'chat_model'],
            'additionalProperties' => false,
        ];
    }

    private function parse(string $content): ?Route
    {
        // Некоторые модели оборачивают JSON в разметку кода.
        $json = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($content)));
        $data = json_decode($json, true);

        $intents = [Route::CHAT, ...array_keys(Route::TOOLS)];

        if (! is_array($data) || ! in_array($data['intent'] ?? null, $intents, true)) {
            Log::warning('Диспетчер чата: непонятный ответ', ['content' => mb_substr($content, 0, 300)]);

            return null;
        }

        return new Route(
            intent: $data['intent'],
            prompt: trim((string) ($data['prompt'] ?? '')),
            confidence: max(0.0, min(1.0, (float) ($data['confidence'] ?? 0))),
            chatModel: in_array($data['chat_model'] ?? null, Route::CHAT_MODELS, true) ? $data['chat_model'] : null,
        );
    }
}
