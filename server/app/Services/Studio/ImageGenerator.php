<?php

namespace App\Services\Studio;

/**
 * Генератор изображений.
 *
 * Логика Студии не знает, кто рисует: она отдаёт описание и получает
 * готовый файл. Смена провайдера — это новая реализация интерфейса.
 */
interface ImageGenerator
{
    /**
     * Доступна ли генерация.
     *
     * Пока провайдер не открыл доступ, интерфейс показывает, что
     * Студия скоро заработает, вместо ошибки на весь экран.
     */
    public function isAvailable(): bool;

    /**
     * Создаёт изображение и возвращает его содержимое.
     *
     * @throws GenerationFailed
     */
    public function generate(GenerationRequest $request): GeneratedImage;
}
