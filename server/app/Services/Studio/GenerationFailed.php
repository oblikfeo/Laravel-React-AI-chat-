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
}
