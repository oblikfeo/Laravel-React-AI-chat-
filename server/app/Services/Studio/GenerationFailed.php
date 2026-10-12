<?php

namespace App\Services\Studio;

use RuntimeException;
use Throwable;

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

    /**
     * Любой сбой в виде несостоявшейся генерации.
     *
     * Непредвиденную ошибку записываем в журнал целиком: человеку
     * покажем обычную фразу, а разбираться нужно по настоящей причине.
     */
    public static function from(Throwable $exception): self
    {
        if ($exception instanceof self) {
            return $exception;
        }

        report($exception);

        return new self('Внутренний сбой: '.$exception->getMessage(), previous: $exception);
    }

    public static function temporary(string $message): self
    {
        $failure = new self($message);
        $failure->temporary = true;

        return $failure;
    }
}
