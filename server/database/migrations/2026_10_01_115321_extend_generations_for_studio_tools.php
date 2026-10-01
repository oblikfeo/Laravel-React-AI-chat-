<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Студия выросла с одной генерации до набора инструментов.
     *
     * Редактирование, увеличение и удаление фона работают от готового
     * файла, поэтому появляется ссылка на исходник. Озвучка хранится
     * там же: работа есть работа, меняется только её вид.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            // Что именно делали: generate, edit, combine, upscale,
            // background_remove, speech, music.
            $table->string('operation')->default('generate')->after('kind');

            // Исходное изображение для инструментов, работающих с
            // готовым файлом. Своя работа или загруженный файл.
            $table->foreignId('source_generation_id')->nullable()
                ->after('operation')
                ->constrained('generations')->nullOnDelete();

            $table->string('source_path')->nullable()->after('source_generation_id');

            // Сколько картинок просили за один раз.
            $table->unsignedTinyInteger('variants')->default(1)->after('seed');

            // Тип файла результата: у аудио он не картиночный.
            $table->string('mime')->nullable()->after('path');

            $table->index(['operation', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropIndex(['operation', 'created_at']);
            $table->dropConstrainedForeignId('source_generation_id');
            $table->dropColumn(['operation', 'source_path', 'variants', 'mime']);
        });
    }
};
