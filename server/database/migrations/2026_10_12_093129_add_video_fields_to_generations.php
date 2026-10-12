<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Видео: разрешение ролика и адрес готового файла.
     *
     * Часть моделей провайдер отдаёт не ответом на вопрос о
     * готовности, а отдельной ссылкой, которую сообщает сразу при
     * постановке задачи. Её надо помнить до конца работы.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->string('resolution', 16)->nullable()->after('aspect_ratio');
            $table->text('download_url')->nullable()->after('queue_id');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn(['resolution', 'download_url']);
        });
    }
};
