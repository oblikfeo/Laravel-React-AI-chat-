<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Звук генерируется долго и идёт через очередь провайдера.
     *
     * Запрос ставится в работу и возвращает номер задачи, результат
     * забирается отдельно. Поэтому работа живёт в состоянии «готовится»
     * и должна переживать перезагрузку страницы.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            // Номер задачи у провайдера: по нему забираем результат.
            $table->string('queue_id')->nullable()->after('status');

            // Сколько примерно займёт, в миллисекундах: показываем
            // ожидание, а не бесконечную крутилку.
            $table->unsignedInteger('expected_ms')->nullable()->after('queue_id');

            // Длительность записи и слова песни.
            $table->unsignedSmallInteger('duration')->nullable()->after('variants');
            $table->text('lyrics')->nullable()->after('negative_prompt');

            $table->index('queue_id');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropIndex(['queue_id']);
            $table->dropColumn(['queue_id', 'expected_ms', 'duration', 'lyrics']);
        });
    }
};
