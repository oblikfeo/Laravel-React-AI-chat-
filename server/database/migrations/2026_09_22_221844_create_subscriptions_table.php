<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Подписка пользователя на платный тариф.
     *
     * Хранится отдельно от поля plan в users: там текущий тариф для
     * интерфейса, здесь — история и срок действия. Так видно, когда
     * подписка заканчивается и продлевается ли она автоматически.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('plan');
            $table->string('period');
            $table->string('status');

            // Платёжная система пока не подключена: идентификатор её
            // подписки появится, когда придут реквизиты.
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();

            // Сумма в копейках: дробные рубли в деньгах недопустимы.
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('RUB');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Ищем активную подписку пользователя на каждом запросе.
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
