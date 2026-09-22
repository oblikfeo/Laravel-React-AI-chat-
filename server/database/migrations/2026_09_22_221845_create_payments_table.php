<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * История платежей.
     *
     * Каждая попытка оплаты — отдельная запись, включая неудачные.
     * Нужна для сверки с платёжной системой и для ответа на вопрос
     * «за что списали деньги».
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->string('plan');
            $table->string('period');
            $table->string('status');

            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('RUB');

            $table->string('provider')->nullable();

            // Идентификатор платежа в платёжной системе. Уникален:
            // уведомление о платеже может прийти несколько раз, и по
            // нему мы узнаём уже обработанный платёж.
            $table->string('provider_id')->nullable()->unique();

            // Ответ платёжной системы целиком: при разборе спорных
            // случаев важно видеть исходные данные, а не пересказ.
            $table->json('payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
