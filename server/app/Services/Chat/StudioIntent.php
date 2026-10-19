<?php

namespace App\Services\Chat;

use App\Models\Generation;

/**
 * Просит ли человек в чате то, что делает Студия.
 *
 * Картинки чат делает сам моделями Студии: рисует с нуля, правит по
 * описанию, объединяет, увеличивает, убирает фон. Звук и видео — нет:
 * на такую просьбу он отвечает карточкой со ссылкой в нужный раздел
 * Студии, где форма уже заполнена.
 *
 * Узнаём по словам до обращения к модели: ответ нужен сразу и
 * бесплатно. Правка распознаётся, только когда есть что править —
 * картинка приложена к сообщению или пришла предыдущим ответом.
 */
class StudioIntent
{
    public const IMAGE = 'image';

    public const EDIT = 'edit';

    public const AUDIO = 'audio';

    public const VIDEO = 'video';

    /** Виды звука: так называются режимы вкладки Audio. */
    public const AUDIO_MUSIC = 'music';

    public const AUDIO_SPEECH = 'speech';

    public const AUDIO_EFFECT = 'effect';

    /** Глаголы «сделать». */
    private const MAKE = '(создай|сделай|сгенерируй|сгенерь|сочини|запиши|спой|сними|смонтируй|make|create|generate|compose|produce|record|render)';

    /** Звук: что сделать. */
    private const MUSIC = '(музык|песн|трек|мелоди|бит\b|саундтрек|джингл|рингтон|music|song|track|melody|beat|soundtrack|jingle)';

    private const EFFECT = '(звуков\w*\s+эффект|звук\b|звука\b|звуки\b|sound\s*effects?|sfx|\bsound\b)';

    /** Озвучка: глагол сам по себе означает звук. */
    private const SPEECH = '/\b(озвучь|озвучить|зачитай|прочитай\s+вслух)|\b(text\s+to\s+speech|read\s+(this|it)\s+aloud|voice\s*over|narrate)\b/iu';

    /** Видео: что сделать. */
    private const VIDEO_WORDS = '(видео|ролик|клип|мульт|анимаци|video|clip|movie|animation)';

    /** Оживить картинку. */
    private const ANIMATE = '/\b(оживи|анимируй)|\b(animate|bring\s+(this|it|the\s+\w+)\s+to\s+life)\b/iu';

    /** Что сделать с картинкой. */
    private const EDIT_WORDS = '/\b(убер|убра|удал|сотр|замен|помен|добав|отредакт|редакт|измени|перекрас|вырез|оставь|оставить|размо|раскрас|улучш|увелич|сделай|поверни|фон|объедин|совмест|соедин|склей|скомбинир)'
        .'|\b(remove|erase|delete|replace|change|add|edit|retouch|cut\s+out|keep\s+only|blur|colou?ri[sz]e|upscale|enhance|make|turn|background|combine|merge|blend)\b/iu';

    /** Объединить несколько картинок. */
    private const COMBINE = '/\b(объедин|совмест|соедин|склей|скомбинир|вместе)|\b(combine|merge|blend|together)\b/iu';

    /** Убрать фон. */
    private const BACKGROUND = '/(\bфон|\bbackground\b)/iu';

    private const REMOVE = '/\b(убер|убра|удал|сотр|вырез|без\b)|\b(remove|erase|delete|cut\s+out|without|no)\b/iu';

    /** Увеличить, не меняя картинку. */
    private const UPSCALE = '/\b(увелич|улучши\s+качеств|повысь\s+разрешени|в\s+высок\w*\s+разрешени)|\b(upscale|enhance\s+(the\s+)?(quality|resolution)|higher\s+resolution)\b/iu';

    /** Нарисовать новую: повелительное слово и то, что рисовать. */
    private const IMAGE_WORDS = [
        '/\b(нарисуй|сгенерируй|сгенерь|изобрази)/iu',
        '/\b(создай|сделай)\b.{0,40}?\b(картинк|изображени|рисун|фото|арт|постер|логотип|аватар|иллюстрац|обо[ий]|портрет)/iu',
        '/^\W*(please\s+)?(draw|paint|sketch)\b/iu',
        '/\b(can|could)\s+you\s+(draw|paint|sketch)\b/iu',
        '/\b(generate|create|make|render|design)\b.{0,40}?\b(image|picture|pic|photo|drawing|illustration|poster|logo|avatar|portrait|wallpaper|artwork)s?\b/iu',
        '/^\W*(an?\s+)?(image|picture|photo)\s+of\b/iu',
    ];

    /**
     * @param  int  $images  сколько картинок можно взять в работу
     * @return array{mode: string, operation: string, prompt: string}|null
     */
    public function detect(string $text, int $images = 0): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // Звук и видео проверяем раньше картинок: в «сгенерируй
        // музыку» глагол тот же, что в «сгенерируй собаку».
        return $this->audio($text)
            ?? $this->video($text, $images)
            ?? $this->image($text)
            ?? $this->edit($text, $images);
    }

    private function audio(string $text): ?array
    {
        if (preg_match(self::SPEECH, $text)) {
            return $this->intent(self::AUDIO, self::AUDIO_SPEECH, $text);
        }

        if ($this->made($text, self::MUSIC)) {
            return $this->intent(self::AUDIO, self::AUDIO_MUSIC, $text);
        }

        if ($this->made($text, self::EFFECT)) {
            return $this->intent(self::AUDIO, self::AUDIO_EFFECT, $text);
        }

        return null;
    }

    private function video(string $text, int $images): ?array
    {
        if ($images > 0 && preg_match(self::ANIMATE, $text)) {
            return $this->intent(self::VIDEO, Generation::OP_ANIMATE, $text);
        }

        if ($this->made($text, self::VIDEO_WORDS)) {
            return $this->intent(self::VIDEO, $images > 0 ? Generation::OP_ANIMATE : Generation::OP_VIDEO, $text);
        }

        return null;
    }

    private function image(string $text): ?array
    {
        foreach (self::IMAGE_WORDS as $pattern) {
            if (preg_match($pattern, $text)) {
                return $this->intent(self::IMAGE, Generation::OP_GENERATE, $text);
            }
        }

        return null;
    }

    private function edit(string $text, int $images): ?array
    {
        if ($images === 0 || ! preg_match(self::EDIT_WORDS, $text)) {
            return null;
        }

        $operation = match (true) {
            $images > 1 && (bool) preg_match(self::COMBINE, $text) => Generation::OP_COMBINE,
            preg_match(self::BACKGROUND, $text) && preg_match(self::REMOVE, $text) => Generation::OP_BACKGROUND,
            (bool) preg_match(self::UPSCALE, $text) => Generation::OP_UPSCALE,
            default => Generation::OP_EDIT,
        };

        return $this->intent(self::EDIT, $operation, $text);
    }

    /** Есть ли глагол «сделать» и рядом с ним — то, что делать. */
    private function made(string $text, string $what): bool
    {
        return (bool) preg_match('/\b'.self::MAKE.'\b.{0,40}?\b'.$what.'/iu', $text);
    }

    /**
     * @return array{mode: string, operation: string, prompt: string}
     */
    private function intent(string $mode, string $operation, string $text): array
    {
        // Просьба уходит как есть: Студия сама переведёт её на
        // английский, а «нарисуй» модели рисования не мешает.
        return ['mode' => $mode, 'operation' => $operation, 'prompt' => mb_substr($text, 0, 2000)];
    }
}
