<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Посетитель без учётной записи.
     *
     * Узнаём его по куке, а при её потере — по отпечатку браузера.
     * IP сюда не входит намеренно: за одним адресом сидит весь дом,
     * офис или кофейня, и по нему нельзя утверждать, что это тот же
     * человек. IP используется отдельно, только как потолок запросов.
     */
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();

            // Значение куки: основной способ узнать посетителя.
            $table->string('token', 64)->unique();

            // Отпечаток браузера: экран, часовой пояс, язык и прочее.
            // Помогает узнать человека, который очистил куки.
            $table->string('fingerprint', 64)->nullable()->index();

            // Последний адрес — для ограничения по IP и разбора накруток.
            $table->string('ip', 45)->nullable()->index();
            $table->string('user_agent')->nullable();

            // Учётная запись, если посетитель потом зарегистрировался.
            // Нужна, чтобы перенести его переписку и не считать лимит
            // дважды.
            $table->foreignId('user_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
