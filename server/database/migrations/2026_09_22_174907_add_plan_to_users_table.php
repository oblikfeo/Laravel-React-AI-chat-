<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Тариф нужен интерфейсу: баннер и меню показываются по-разному
     * тем, кто уже платит, и тем, кто на бесплатном.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }
};
