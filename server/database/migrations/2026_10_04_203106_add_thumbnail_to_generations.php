<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Уменьшенная копия для ленты.
     *
     * Картинки весят по несколько мегабайт, а в ленте показываются
     * квадратиками в двести точек: тянуть ради них полный файл —
     * долго и незачем.
     */
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};
