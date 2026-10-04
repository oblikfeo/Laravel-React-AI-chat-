<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Работы в общей ленте.
     *
     * Человек выбирает при создании, показывать работу другим или
     * оставить себе. По умолчанию показываем: лента без работ никому
     * не интересна, а скрыть можно одним переключателем.
     *
     * Звук и работы безцензурных моделей в ленту не попадают, см.
     * область видимости в модели.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('status');

            // Лента показывает свежие работы всех людей сразу.
            $table->index(['is_public', 'kind', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'kind', 'status', 'id']);
            $table->dropColumn('is_public');
        });
    }
};
