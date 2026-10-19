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

    /** Глаголы «сделать» в любой форме: сделай, сделать, сделаешь. */
    private const MAKE = '(созда\w*|сдела\w*|сгенер\w*|сочин\w*|запиш\w*|записа\w*|спо[йе]\w*|спеть|сним\w*|снять|смонтир\w*|нарис\w*|make|create|generate|compose|produce|record|render|draw)';

    /** Желание без глагола: «хочу видео», «нужна песня». */
    private const WISH = "(хочу|хотел[аи]?\s+бы|нуж[нен]\w*|надо|давай|можешь|можно|сможешь|want|need|would\s+like|i'?d\s+like|can\s+you|could\s+you)";

    /**
     * Вопрос о том, как что-то сделать, — это вопрос, а не просьба:
     * на «как сделать видео для тиктока» нужен ответ словами.
     */
    private const QUESTION = '/^\W*(как|почему|зачем|что\s+такое|какой|какая|какие|где|сколько|посоветуй|расскажи|объясни|how|why|what|which|where|explain|tell\s+me)\b/iu';

    /** Звук: что сделать. */
    private const MUSIC = '(музык|песн|трек|мелоди|бит\b|саундтрек|джингл|рингтон|аудио|рэп|гимн|music|song|track|melody|beat|soundtrack|jingle|audio|rap|anthem)';

    /** Голос: озвучка, подкаст, голосовое. */
    private const VOICE = '(голос|озвучк|подкаст|диктор|voice|podcast|narration|speech)';

    private const EFFECT = '(звуков\w*\s+эффект|звук\b|звука\b|звуки\b|sound\s*effects?|sfx|\bsound\b)';

    /** Озвучка: глагол сам по себе означает звук. */
    private const SPEECH = '/\b(озвучь|озвучить|зачитай|прочитай\s+вслух)|\b(text\s+to\s+speech|read\s+(this|it)\s+aloud|voice\s*over|narrate)\b/iu';

    /** Видео: что сделать. */
    private const VIDEO_WORDS = '(видео|ролик|клип|мульт|анимаци|video|clip|movie|animation)';

    /** Ссылка на картинку, которая уже есть: «из неё», «этой фото». */
    private const REFERENCE = '/(?<!\w)(эт[оуаи]\w*|е[ёе]|его|него|н[её]е|из\s+(фото|картинк|изображени)|this|it|the\s+(photo|picture|image))(?!\w)/iu';

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

    /** Рисовать: этот глагол сам по себе означает картинку. */
    private const DRAW = "/\b(нарису\w*|нарисова\w*|изобраз\w*)|^\W*(please\s+)?(draw|paint|sketch)\b|\b(can|could)\s+you\s+(draw|paint|sketch)\b|^\W*(an?\s+)?(image|picture|photo)\s+of\b/iu";

    /** Картинка: что нарисовать. */
    private const IMAGE_NOUNS = '(картинк|изображени|рисун|фото|арт|постер|логотип|аватар|иллюстрац|обо[ий]|портрет|баннер|стикер|image|picture|pic\b|photo|drawing|illustration|poster|logo|avatar|portrait|wallpaper|artwork|banner|sticker)';

    /** «Сгенерируй» без уточнения: картинка, если просят не текст. */
    private const GENERATE = '/\b(сгенер\w*|generate)\b/iu';

    /**
     * Текст: «сгенерируй пароль», «сгенерируй стих», «generate code» —
     * это работа текстовой модели, а не рисование.
     */
    private const TEXT_NOUNS = '(текст|стих|рассказ|истори|сказк|стать|пост(?!ер)|письм|сообщени|код|скрипт|функци|программ|парол|им[яе]н?|назван|иде[июйя]|список|план|рецепт|резюме|сценари|диалог|ответ|вопрос|тест|задач|шутк|анекдот|тост|поздравлен|слоган|описани|отзыв|таблиц|json|sql|регуляр|промпт|номер|числ|документ|презентаци|отч[её]т|эссе|реферат|перевод|ключ|токен|данн|пример|слов|фраз|загадк|кроссворд|никнейм|ник\b|хештег|хэштег|заголов|подпис'
        .'|text|poem|story|essay|article|post(?!er)|email|letter|message|code|script|function|program|password|name|title|idea|list|plan|recipe|resume|cv\b|summary|dialogue|answer|question|quiz|joke|toast|slogan|description|review|table|json|sql|regex|prompt|number|report|translation|key|token|data|example|word|phrase|riddle|tagline|caption|bio\b|nickname|hashtag|headline|uuid)';

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

        // «Как сделать…», «расскажи…» — вопрос для модели, даже если в
        // диалоге есть картинка: «как убрать фон в фотошопе?» — не
        // просьба убрать фон.
        if (preg_match(self::QUESTION, $text)) {
            return null;
        }

        // Звук и видео проверяем раньше картинок: в «сгенерируй
        // музыку» глагол тот же, что в «сгенерируй собаку».
        return $this->audioOrVideo($text, $images)
            ?? $this->image($text)
            ?? $this->edit($text, $images);
    }

    /**
     * Звук или видео.
     *
     * Если названо и то и другое, решает то, что названо первым:
     * «клип для песни» — видео, «музыка для ролика» — звук.
     */
    private function audioOrVideo(string $text, int $images): ?array
    {
        if (preg_match(self::SPEECH, $text)) {
            return $this->intent(self::AUDIO, self::AUDIO_SPEECH, $text);
        }

        if ($images > 0 && preg_match(self::ANIMATE, $text)) {
            return $this->intent(self::VIDEO, Generation::OP_ANIMATE, $text);
        }

        $found = array_filter([
            self::AUDIO_SPEECH => $this->made($text, self::VOICE),
            self::AUDIO_MUSIC => $this->made($text, self::MUSIC),
            self::AUDIO_EFFECT => $this->made($text, self::EFFECT),
            self::VIDEO => $this->made($text, self::VIDEO_WORDS),
        ], fn (?int $position) => $position !== null);

        if (! $found) {
            return null;
        }

        asort($found);
        $first = array_key_first($found);

        if ($first !== self::VIDEO) {
            return $this->intent(self::AUDIO, $first, $text);
        }

        // Ролик из картинки — только если о ней и речь: «сделай из неё
        // видео». Иначе «хочу видео с котом» после картинки с собакой
        // оживил бы собаку.
        $animate = $images > 0 && preg_match(self::REFERENCE, $text);

        return $this->intent(self::VIDEO, $animate ? Generation::OP_ANIMATE : Generation::OP_VIDEO, $text);
    }

    private function image(string $text): ?array
    {
        $drawn = preg_match(self::DRAW, $text)
            || $this->made($text, self::IMAGE_NOUNS) !== null
            // «Сгенерируй закат» — картинка, «сгенерируй пароль» — нет.
            || (preg_match(self::GENERATE, $text) && $this->made($text, self::TEXT_NOUNS) === null);

        return $drawn ? $this->intent(self::IMAGE, Generation::OP_GENERATE, $text) : null;
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

    /**
     * Где названо то, что просят сделать, — если рядом есть «сделать»
     * или «хочу». Null — не просят.
     */
    private function made(string $text, string $what): ?int
    {
        $found = preg_match(
            '/\b('.self::MAKE.'|'.self::WISH.')\b.{0,40}?\b(?<what>'.$what.')/iu',
            $text,
            $match,
            PREG_OFFSET_CAPTURE,
        );

        return $found ? $match['what'][1] : null;
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
