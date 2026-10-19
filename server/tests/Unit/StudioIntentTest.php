<?php

namespace Tests\Unit;

use App\Models\Generation;
use App\Services\Chat\StudioIntent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Что чат понимает под просьбой.
 *
 * Таблица реальных формулировок: для каждой — что должно произойти.
 * null — обычный вопрос, отвечает текстовая модель.
 */
class StudioIntentTest extends TestCase
{
    private const TEXT = null;

    private const IMAGE = ['image', Generation::OP_GENERATE];

    private const MUSIC = ['audio', 'music'];

    private const SPEECH = ['audio', 'speech'];

    private const EFFECT = ['audio', 'effect'];

    private const VIDEO = ['video', Generation::OP_VIDEO];

    private const ANIMATE = ['video', Generation::OP_ANIMATE];

    private const EDIT = ['edit', Generation::OP_EDIT];

    private const BACKGROUND = ['edit', Generation::OP_BACKGROUND];

    private const UPSCALE = ['edit', Generation::OP_UPSCALE];

    private const COMBINE = ['edit', Generation::OP_COMBINE];

    /** Без картинки в диалоге. */
    public static function withoutPicture(): array
    {
        return [
            // Картинки.
            ['нарисуй собаку', self::IMAGE],
            ['Нарисуй кота в космосе', self::IMAGE],
            ['можешь нарисовать собаку?', self::IMAGE],
            ['изобрази закат над морем', self::IMAGE],
            ['сгенерируй собаку', self::IMAGE],
            ['сгенерируй закат над горами', self::IMAGE],
            ['создай фото собаки', self::IMAGE],
            ['сделай картинку с котом', self::IMAGE],
            ['хочу картинку с закатом', self::IMAGE],
            ['нужен логотип для кофейни', self::IMAGE],
            ['создай логотип для кафе', self::IMAGE],
            ['сделай аватарку в стиле аниме', self::IMAGE],
            ['draw a red fox in the snow', self::IMAGE],
            ['Draw me a castle', self::IMAGE],
            ['can you draw a dragon?', self::IMAGE],
            ['generate a cat in space', self::IMAGE],
            ['create an image of a dog', self::IMAGE],
            ['Create an image of Trump in the guise of Kim Jong Un.', self::IMAGE],
            ['make a picture of a sunset', self::IMAGE],
            ['I want a picture of a red fox', self::IMAGE],
            ['an image of a lighthouse at night', self::IMAGE],
            ['generate a poster for a jazz night', self::IMAGE],

            // Музыка и звук.
            ['сгенерируй аудио', self::MUSIC],
            ['сгенерируй музыку для вечеринки', self::MUSIC],
            ['сгенерируй музыку для ролика', self::MUSIC],
            ['создай песню про лето', self::MUSIC],
            ['хочу песню про лето', self::MUSIC],
            ['нужна музыка для вечеринки', self::MUSIC],
            ['сделай трек в стиле lo-fi', self::MUSIC],
            ['сочини мелодию для заставки', self::MUSIC],
            ['сделай рэп про программистов', self::MUSIC],
            ['generate a song about summer', self::MUSIC],
            ['I need a song for my birthday', self::MUSIC],
            ['make some music for my video', self::MUSIC],
            ['create an audio jingle', self::MUSIC],
            ['сделай звук открывающейся двери', self::EFFECT],
            ['нужен звуковой эффект взрыва', self::EFFECT],
            ['generate a sound effect of rain', self::EFFECT],
            ['озвучь: добро пожаловать', self::SPEECH],
            ['озвучь этот текст', self::SPEECH],
            ['зачитай вслух стих', self::SPEECH],
            ['сгенерируй голос диктора', self::SPEECH],
            ['сделай озвучку для ролика', self::SPEECH],
            ['хочу подкаст про космос', self::SPEECH],
            ['make a voiceover for my ad', self::SPEECH],
            ['text to speech: hello world', self::SPEECH],

            // Видео.
            ['хочу сделать видео', self::VIDEO],
            ['хочу видео с котом на пляже', self::VIDEO],
            ['сгенерируй видео с котом', self::VIDEO],
            ['сделай видео с собакой', self::VIDEO],
            ['можешь сделать ролик про космос?', self::VIDEO],
            ['мне нужен клип для песни', self::VIDEO],
            ['сними ролик про осень', self::VIDEO],
            ['создай анимацию прыгающего мяча', self::VIDEO],
            ['I want to make a video', self::VIDEO],
            ['make a video of a dog running', self::VIDEO],
            ['can you make a video of a cat', self::VIDEO],
            ['generate a short clip of waves', self::VIDEO],

            // Текст: «сгенерируй», но не картинка.
            ['сгенерируй пароль', self::TEXT],
            ['сгенерируй надёжный пароль из 16 символов', self::TEXT],
            ['сгенерируй имя для собаки', self::TEXT],
            ['сгенерируй идеи для бизнеса', self::TEXT],
            ['сгенерируй текст поздравления', self::TEXT],
            ['сгенерируй стих про осень', self::TEXT],
            ['сгенерируй код на python', self::TEXT],
            ['сгенерируй список покупок', self::TEXT],
            ['сгенерируй название для кафе', self::TEXT],
            ['сгенерируй слоган для магазина', self::TEXT],
            ['сгенерируй план тренировок', self::TEXT],
            ['сгенерируй рецепт пасты', self::TEXT],
            ['сгенерируй пост для инстаграма', self::TEXT],
            ['сгенерируй sql запрос', self::TEXT],
            ['generate a password', self::TEXT],
            ['generate a name for my startup', self::TEXT],
            ['generate python code for sorting', self::TEXT],
            ['generate ideas for a party', self::TEXT],
            ['generate a poem about the sea', self::TEXT],
            ['create a workout plan', self::TEXT],

            // Вопросы и обычные просьбы.
            ['как сделать видео для тиктока?', self::TEXT],
            ['Как нарисовать собаку карандашом?', self::TEXT],
            ['какая песня была популярной в 1999?', self::TEXT],
            ['Какая песня была самой популярной в 1999 году?', self::TEXT],
            ['посоветуй хорошее видео про космос', self::TEXT],
            ['расскажи про музыку барокко', self::TEXT],
            ['объясни, как работает фотосинтез', self::TEXT],
            ['хочу понять квантовую физику', self::TEXT],
            ['нужно написать письмо начальнику', self::TEXT],
            ['напиши текст песни про лето', self::TEXT],
            ['напиши сценарий для видео', self::TEXT],
            ['переведи на английский: привет', self::TEXT],
            ['привет, расскажи что ты умеешь', self::TEXT],
            ['что ты умеешь?', self::TEXT],
            ['создай план тренировок', self::TEXT],
            ['сделай список покупок', self::TEXT],
            ['придумай название для кафе', self::TEXT],
            ['сделай презентацию', self::TEXT],
            ['удали дубликаты из списка', self::TEXT],
            ['убери лишние слова из текста', self::TEXT],
            ['Объясни теорию относительности', self::TEXT],
            ['how do I make a video for youtube?', self::TEXT],
            ['what is the best song of 2020?', self::TEXT],
            ['tell me about impressionist painting', self::TEXT],
            ['write an email to my boss', self::TEXT],
        ];
    }

    /** Картинка уже есть: приложена или пришла прошлым ответом. */
    public static function withPicture(): array
    {
        return [
            ['убери фон', self::BACKGROUND, 1],
            ['удали фон с фото', self::BACKGROUND, 1],
            ['сделай без фона', self::BACKGROUND, 1],
            ['remove the background', self::BACKGROUND, 1],
            ['увеличь качество', self::UPSCALE, 1],
            ['увеличь разрешение', self::UPSCALE, 1],
            ['upscale it', self::UPSCALE, 1],
            ['сделай небо закатным', self::EDIT, 1],
            ['замени собаку на кота', self::EDIT, 1],
            ['добавь шляпу', self::EDIT, 1],
            ['убери людей, оставь девушку в розовом', self::EDIT, 1],
            ['change the sky to night', self::EDIT, 1],
            ['объедини эти фото в одно', self::COMBINE, 2],
            ['совмести две картинки', self::COMBINE, 2],
            ['combine these images', self::COMBINE, 2],
            ['оживи это фото', self::ANIMATE, 1],
            ['оживи её', self::ANIMATE, 1],
            ['сделай из этой картинки видео', self::ANIMATE, 1],
            ['animate this picture', self::ANIMATE, 1],
            ['нарисуй другую собаку', self::IMAGE, 1],
            ['хочу видео с котом', self::VIDEO, 1],
            ['сделай видео для всего класса', self::VIDEO, 1],
            ['какой породы эта собака?', self::TEXT, 1],
            ['что на этой картинке?', self::TEXT, 1],
            ['как убрать фон в фотошопе?', self::TEXT, 1],
            ['опиши эту картинку', self::TEXT, 1],
        ];
    }

    #[DataProvider('withoutPicture')]
    public function test_request_without_picture(string $text, ?array $expected): void
    {
        $this->assertResult($text, $expected, 0);
    }

    #[DataProvider('withPicture')]
    public function test_request_with_picture(string $text, ?array $expected, int $images): void
    {
        $this->assertResult($text, $expected, $images);
    }

    private function assertResult(string $text, ?array $expected, int $images): void
    {
        $result = (new StudioIntent)->detect($text, $images);

        $this->assertSame(
            $expected,
            $result ? [$result['mode'], $result['operation']] : null,
            "«{$text}»",
        );
    }
}
