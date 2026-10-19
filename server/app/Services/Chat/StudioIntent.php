<?php

namespace App\Services\Chat;

use App\Models\Generation;

/**
 * Просит ли человек в чате картинку.
 *
 * Как у Venice: «нарисуй собаку» — в ответ приходит картинка, «убери
 * фон» следующим сообщением — та же картинка без фона. Рисует не
 * текстовая модель, а модели Студии, поэтому просьбу надо узнать до
 * обращения к модели.
 *
 * Узнаём по словам: ответ нужен сразу и бесплатно. Правка
 * распознаётся, только когда есть что править — картинка приложена к
 * сообщению или пришла предыдущим ответом.
 */
class StudioIntent
{
    public const IMAGE = 'image';
    public const EDIT = 'edit';

    /** Что сделать с картинкой. */
    private const EDIT_WORDS = '/\b(убер|убра|удал|сотр|замен|помен|добав|отредакт|редакт|измени|перекрас|вырез|оставь|оставить|размо|раскрас|улучш|увелич|сделай|поверни|фон)'
        .'|\b(remove|erase|delete|replace|change|add|edit|retouch|cut\s+out|keep\s+only|blur|colou?ri[sz]e|upscale|enhance|make|turn|background)\b/iu';

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
     * @param  bool  $hasSource  есть ли картинка, которую можно править
     * @return array{mode: string, operation: string, prompt: string}|null
     */
    public function detect(string $text, bool $hasSource): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // Новая картинка важнее правки: «нарисуй другую» при старой
        // картинке в диалоге — это новая.
        foreach (self::IMAGE_WORDS as $pattern) {
            if (preg_match($pattern, $text)) {
                return $this->intent(self::IMAGE, Generation::OP_GENERATE, $text);
            }
        }

        if (! $hasSource || ! preg_match(self::EDIT_WORDS, $text)) {
            return null;
        }

        if (preg_match(self::BACKGROUND, $text) && preg_match(self::REMOVE, $text)) {
            return $this->intent(self::EDIT, Generation::OP_BACKGROUND, $text);
        }

        if (preg_match(self::UPSCALE, $text)) {
            return $this->intent(self::EDIT, Generation::OP_UPSCALE, $text);
        }

        return $this->intent(self::EDIT, Generation::OP_EDIT, $text);
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
