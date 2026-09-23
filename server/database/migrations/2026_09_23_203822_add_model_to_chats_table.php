<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Выбранная модель запоминается за чатом.
     *
     * Человек выбирает её один раз и продолжает разговор: переспрашивать
     * при каждом сообщении не нужно. Храним наш ключ (auto, smart), а не
     * название у провайдера — его можно сменить, не трогая данные.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->string('model_key')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('model_key');
        });
    }
};
