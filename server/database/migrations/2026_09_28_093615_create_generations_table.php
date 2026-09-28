<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Работы, созданные в Студии.
     *
     * Владельцем может быть пользователь или гость — так же, как у
     * чатов: человек должен попробовать Студию до регистрации.
     *
     * Сам файл лежит в хранилище, в базе только путь и то, из чего
     * работа сделана: по этим полям работает повторная генерация.
     */
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()
                ->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()
                ->constrained()->cascadeOnDelete();

            // image или video: раздел один, виды работ разные.
            $table->string('kind')->default('image');
            $table->string('model_key');
            $table->string('status')->default('pending');

            $table->text('prompt');
            $table->text('negative_prompt')->nullable();
            $table->string('aspect_ratio')->default('1:1');
            $table->string('style')->nullable();

            // Зерно: одно и то же значение даёт повторяемый результат.
            $table->unsignedBigInteger('seed')->nullable();

            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // Причина неудачи — для разбора, пользователю не показывается.
            $table->text('failure_reason')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Лента работ владельца — самый частый запрос.
            $table->index(['user_id', 'created_at']);
            $table->index(['guest_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
