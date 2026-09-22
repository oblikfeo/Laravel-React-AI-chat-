<?php

namespace App\Services\Billing;

use App\Models\Payment;

/**
 * Платёжная система.
 *
 * Логика подписок не знает, кто принимает деньги: она создаёт платёж
 * и получает ссылку на оплату. Подключение ЮKassa сводится к реализации
 * этого интерфейса и вписыванию реквизитов в настройки.
 */
interface PaymentGateway
{
    /**
     * Готова ли система принимать платежи.
     *
     * Пока реквизиты не заданы, интерфейс показывает, что оплата
     * временно недоступна, вместо ссылки в никуда.
     */
    public function isConfigured(): bool;

    /**
     * Создаёт платёж и возвращает адрес страницы оплаты.
     */
    public function createPayment(Payment $payment, string $returnUrl): PaymentIntent;

    /**
     * Разбирает уведомление о платеже.
     *
     * Возвращает null, если уведомление чужое или подпись не сошлась:
     * принимать на веру данные из внешнего запроса нельзя.
     */
    public function parseNotification(array $payload, ?string $signature = null): ?PaymentNotification;
}
