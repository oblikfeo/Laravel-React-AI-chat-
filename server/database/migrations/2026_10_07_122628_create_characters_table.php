<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Персонажи.
     *
     * Персонаж — сохранённая роль для чата: кто он, как говорит и что
     * помнит. Всё, что видит модель (инструкции, память, документ),
     * лежит здесь же и наружу отдаётся только автору.
     */
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();

            // У персонажа всегда есть автор: гость создавать не может.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // То, что видят все: карточка в каталоге.
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('tags')->nullable();

            $table->string('avatar_disk')->nullable();
            $table->string('avatar_path')->nullable();

            // Первая реплика персонажа в новом диалоге.
            $table->text('intro')->nullable();

            // Основа личности. Видна только автору.
            $table->text('instructions');

            // Свой системный запрос целиком — вместо нашей обвязки.
            $table->text('system_prompt')->nullable();

            // Документ со сведениями: текст извлекается при загрузке,
            // модель файлов не открывает.
            $table->string('context_name')->nullable();
            $table->longText('context_text')->nullable();

            // Что персонаж помнит всегда, даже когда ранняя переписка
            // уже не помещается в запрос.
            $table->json('memories')->nullable();

            $table->string('model_key');
            $table->decimal('temperature', 3, 2)->nullable();

            // По умолчанию персонаж личный: публикация — осознанный шаг.
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            $table->index(['is_public', 'id']);
            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
