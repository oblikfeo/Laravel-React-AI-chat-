<?php

namespace App\Services\Studio;

/**
 * Инструменты Студии.
 *
 * Логика Студии не знает, кто рисует: она отдаёт описание и получает
 * готовые файлы. Смена провайдера — это новая реализация интерфейса.
 */
interface ImageGenerator
{
    /**
     * Доступна ли Студия.
     *
     * Пока ключ провайдера не выдан, интерфейс говорит, что раздел
     * скоро откроется, вместо ошибки на весь экран.
     */
    public function isAvailable(): bool;

    /**
     * Рисует по описанию.
     *
     * Возвращает набор: за один запрос можно просить несколько
     * вариантов одной идеи.
     *
     * @return array<int, GeneratedImage>
     * @throws GenerationFailed
     */
    public function generate(GenerationRequest $request): array;

    /**
     * Меняет готовое изображение по описанию.
     *
     * @throws GenerationFailed
     */
    public function edit(EditRequest $request): GeneratedImage;

    /**
     * Собирает одно изображение из нескольких.
     *
     * @param array<int, string> $images содержимое файлов
     * @throws GenerationFailed
     */
    public function combine(array $images, string $prompt, ?string $aspectRatio = null): GeneratedImage;

    /**
     * Увеличивает разрешение.
     *
     * @throws GenerationFailed
     */
    public function upscale(string $image, int $scale = 2, float $creativity = 0.01): GeneratedImage;

    /**
     * Убирает фон.
     *
     * @throws GenerationFailed
     */
    public function removeBackground(string $image): GeneratedImage;

    /**
     * Озвучивает текст.
     *
     * @throws GenerationFailed
     */
    public function speech(SpeechRequest $request): GeneratedAudio;
}
