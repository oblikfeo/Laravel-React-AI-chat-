<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Картинка в ответе чата.
     *
     * Чат рисует и правит картинки теми же моделями, что и Студия, и
     * результат — обычная работа Студии: она же лежит в галерее. Ответ
     * чата просто ссылается на неё.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('generation_id')
                ->nullable()
                ->after('model')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generation_id');
        });
    }
};
