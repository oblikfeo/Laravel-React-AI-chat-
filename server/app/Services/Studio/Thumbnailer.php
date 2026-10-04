<?php

namespace App\Services\Studio;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Уменьшенная копия картинки.
 *
 * В ленте работы показываются небольшими квадратами, а сами файлы
 * весят по несколько мегабайт: без уменьшенной копии браузер тянет
 * их целиком и лента грузится долго.
 */
class Thumbnailer
{
    /** Сторона миниатюры: хватает и для плотных экранов. */
    private const SIZE = 480;

    /**
     * Делает миниатюру или возвращает null, если не вышло.
     *
     * Неудача не должна мешать сохранению работы: лента просто
     * покажет полный файл, как раньше.
     */
    public function make(string $contents): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $source = @imagecreatefromstring($contents);

            if (! $source) {
                return null;
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $side = max($width, $height);

            // Картинка и так маленькая — уменьшать нечего.
            if ($side <= self::SIZE) {
                imagedestroy($source);

                return null;
            }

            $scale = self::SIZE / $side;
            $target = imagecreatetruecolor((int) ($width * $scale), (int) ($height * $scale));

            // Прозрачность важна: у работ с удалённым фоном её
            // потеря превратила бы фон в чёрный.
            imagealphablending($target, false);
            imagesavealpha($target, true);

            imagecopyresampled(
                $target,
                $source,
                0, 0, 0, 0,
                imagesx($target), imagesy($target),
                $width, $height,
            );

            ob_start();
            imagepng($target, null, 6);
            $result = ob_get_clean();

            imagedestroy($source);
            imagedestroy($target);

            return $result ?: null;
        } catch (Throwable $exception) {
            Log::warning('Студия: не удалось сделать миниатюру', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
