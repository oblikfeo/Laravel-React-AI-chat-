<?php

use App\Http\Controllers\Chat\DestroyChatController;
use App\Http\Controllers\Chat\IndexChatController;
use App\Http\Controllers\Chat\ReplyController;
use App\Http\Controllers\Chat\ShowAttachmentController;
use App\Http\Controllers\Chat\ShowChatController;
use App\Http\Controllers\Chat\StoreChatController;
use App\Http\Controllers\Chat\StoreMessageController;
use App\Http\Controllers\Chat\UpdateChatModelController;
use App\Http\Controllers\Billing\HandleWebhookController;
use App\Http\Controllers\Billing\ReturnController;
use App\Http\Controllers\Billing\StoreSubscriptionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Settings\UpdatePasswordController;
use App\Http\Controllers\Settings\UpdateProfileController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('chats', IndexChatController::class)->name('chats.index');
    Route::post('chats', StoreChatController::class)->name('chats.store');
    Route::get('chats/{chat}', ShowChatController::class)->name('chats.show');
    Route::delete('chats/{chat}', DestroyChatController::class)->name('chats.destroy');

    Route::post('chats/{chat}/messages', StoreMessageController::class)
        ->name('chats.messages.store');

    Route::post('chats/{chat}/reply', ReplyController::class)
        ->name('chats.reply');

    Route::put('chats/{chat}/model', UpdateChatModelController::class)
        ->name('chats.model.update');

    Route::get('attachments/{attachment}', ShowAttachmentController::class)
        ->name('attachments.show');

    Route::put('settings/profile', UpdateProfileController::class)
        ->name('settings.profile.update');
    Route::put('settings/password', UpdatePasswordController::class)
        ->name('settings.password.update');

    Route::post('billing/subscribe', StoreSubscriptionController::class)
        ->name('billing.subscribe');
    Route::get('billing/return', ReturnController::class)
        ->name('billing.return');
});

// Уведомление платёжной системы приходит без сессии и без ключа
// защиты от подделки: это запрос сервера к серверу, подлинность
// проверяется обращением к платёжной системе за состоянием платежа.
Route::post('billing/webhook', HandleWebhookController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('billing.webhook');

require __DIR__.'/auth.php';
