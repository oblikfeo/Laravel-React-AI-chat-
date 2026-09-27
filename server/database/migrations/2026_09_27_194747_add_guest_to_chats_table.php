<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Чат гостя.
     *
     * Владельцем может быть либо пользователь, либо гость, поэтому
     * user_id становится необязательным. При регистрации гостевые
     * чаты переходят в учётную запись.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('guest_id')->nullable()->after('user_id')
                ->constrained()->cascadeOnDelete();
        });

        // SQLite не умеет менять столбцы на месте, поэтому смену
        // обязательности делаем отдельно — Laravel сам разберётся.
        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_id');
        });
    }
};
