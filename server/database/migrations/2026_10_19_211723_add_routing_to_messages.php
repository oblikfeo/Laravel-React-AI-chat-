<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Решение диспетчера по сообщению: что человек хотел, с каким
     * описанием и насколько уверенно. Принимается один раз, когда
     * сообщение сохранено, и по нему же строится ответ.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->json('routing')->nullable()->after('intent');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('routing');
        });
    }
};
