<?php

namespace App\Services\Studio;

use RuntimeException;

/**
 * Генерация не удалась.
 *
 * Текст предназначен для лога: пользователю показывается обычная
 * фраза без технических подробностей.
 */
class GenerationFailed extends RuntimeException
{
    /**
     * Сбой на стороне провайдера, который может пройти сам.
     *
     * Важно для долгих задач: из-за минутной неполадки нельзя
     * бросать работу, за которую уже заплачено.
     */
    public bool $temporary = false;

    public static function temporary(string $message): self
    {
        $failure = new self($message);
        $failure->temporary = true;

        return $failure;
    }
}
