<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Диалог с персонажем — обычный чат с привязкой к персонажу.
     *
     * Если персонажа удалят, переписка остаётся: человек её вёл, и
     * терять её из-за чужого решения он не должен.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('character_id')->nullable()
                ->after('guest_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('character_id');
        });
    }
};
