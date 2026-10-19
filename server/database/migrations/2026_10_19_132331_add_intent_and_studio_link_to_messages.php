<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * intent — что человек просил: картинку, правку, звук или видео.
     * По нему чат, пока ждёт ответа, показывает нужную анимацию.
     *
     * studio_link — карточка «сделать это в Студии» в ответе: раздел,
     * вид работы, описание и картинка, с которой Студия откроется.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('intent', 16)->nullable()->after('model');
            $table->json('studio_link')->nullable()->after('generation_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['intent', 'studio_link']);
        });
    }
};
