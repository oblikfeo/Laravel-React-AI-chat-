<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Журнал расходов: что стоило обращение к провайдеру.
     *
     * Одна строка — одно обращение: ответ в чате, работа Студии,
     * служебный вызов. Из него считается, сколько бюджета тарифа
     * израсходовал человек, и куда уходят деньги сервиса.
     *
     * cost — себестоимость в долларах. Работа, которая не получилась,
     * из журнала удаляется: человек за неё не платит.
     */
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('model_key', 60)->nullable();
            $table->string('provider_model', 120)->nullable();
            $table->decimal('cost', 12, 6)->default(0);
            $table->json('meta')->nullable();
            $table->foreignId('generation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
