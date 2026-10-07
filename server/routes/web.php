<?php

use App\Http\Controllers\Characters\DestroyCharacterController;
use App\Http\Controllers\Characters\IndexCharactersController;
use App\Http\Controllers\Characters\ShowCharacterAvatarController;
use App\Http\Controllers\Characters\StartCharacterChatController;
use App\Http\Controllers\Characters\StoreCharacterController;
use App\Http\Controllers\Characters\UpdateCharacterController;
use App\Http\Controllers\Characters\WriteCharacterTextController;
use App\Http\Controllers\Chat\DestroyChatController;
use App\Http\Controllers\Chat\IndexChatController;
use App\Http\Controllers\Chat\ReplyController;
use App\Http\Controllers\Chat\RetryReplyController;
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
use App\Http\Controllers\Studio\DestroyGenerationController;
use App\Http\Controllers\Studio\EditGenerationController;
use App\Http\Controllers\Studio\RetryGenerationController;
use App\Http\Controllers\Studio\ShowGenerationFileController;
use App\Http\Controllers\Studio\ShowStudioController;
use App\Http\Controllers\Studio\StoreGenerationController;
use App\Http\Controllers\Feed\ShowFeedController;
use App\Http\Controllers\Feed\StoreChatFromFeedController;
use App\Http\Controllers\Studio\CollectAudioController;
use App\Http\Controllers\Studio\StoreEffectController;
use App\Http\Controllers\Studio\StoreMusicController;
use App\Http\Controllers\Studio\StoreVoiceChangeController;
use App\Http\Controllers\Studio\StoreSpeechController;
use App\Http\Controllers\Settings\UpdateProfileController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Чат доступен и без учётной записи: гость пробует его с одной
// моделью и небольшим дневным лимитом, см. config/guests.php.
Route::group([], function () {
    Route::get('chats', IndexChatController::class)->name('chats.index');
    Route::post('chats', StoreChatController::class)->name('chats.store');
    Route::get('chats/{chat}', ShowChatController::class)->name('chats.show');
    Route::delete('chats/{chat}', DestroyChatController::class)->name('chats.destroy');

    Route::post('chats/{chat}/messages', StoreMessageController::class)
        ->name('chats.messages.store');

    Route::post('chats/{chat}/reply', ReplyController::class)
        ->name('chats.reply');

    Route::post('chats/{chat}/retry', RetryReplyController::class)
        ->name('chats.retry');

    Route::put('chats/{chat}/model', UpdateChatModelController::class)
        ->name('chats.model.update');

    Route::get('attachments/{attachment}', ShowAttachmentController::class)
        ->name('attachments.show');

    // Общая лента: что люди согласились показать.
    Route::get('feed', ShowFeedController::class)->name('feed');
    // Работу из ленты можно обсудить: заводим диалог с ней.
    Route::post('chats/from-feed', StoreChatFromFeedController::class)
        ->name('chats.from-feed');

    // Персонажи: каталог виден всем, поговорить с открытым
    // персонажем может и гость — в пределах своего дневного лимита.
    Route::get('characters', IndexCharactersController::class)
        ->name('characters.index');
    Route::get('characters/{character}/avatar', ShowCharacterAvatarController::class)
        ->whereNumber('character')
        ->name('characters.avatar');
    Route::post('characters/{character}/chat', StartCharacterChatController::class)
        ->whereNumber('character')
        ->name('characters.chat');

    // Студия открыта и гостю: попробовать до регистрации, с малым
    // дневным лимитом, см. config/studio.php.
    Route::get('studio', ShowStudioController::class)->name('studio');
    Route::post('studio', StoreGenerationController::class)
        ->name('studio.store');
    Route::post('studio/edit', EditGenerationController::class)
        ->name('studio.edit');
    Route::post('studio/speech', StoreSpeechController::class)
        ->name('studio.speech');
    Route::post('studio/music', StoreMusicController::class)
        ->name('studio.music');
    Route::post('studio/effect', StoreEffectController::class)
        ->name('studio.effect');
    Route::post('studio/voice-change', StoreVoiceChangeController::class)
        ->name('studio.voice-change');
    // Звук считается долго: страница спрашивает готовность, пока
    // работа не появится в галерее.
    Route::post('studio/collect', CollectAudioController::class)
        ->name('studio.collect');
    Route::post('studio/{generation}/retry', RetryGenerationController::class)
        ->name('studio.retry');
    Route::delete('studio/{generation}', DestroyGenerationController::class)
        ->name('studio.destroy');
    Route::get('studio/{generation}/file', ShowGenerationFileController::class)
        ->name('studio.file');
});

Route::middleware('auth')->group(function () {
    Route::put('settings/profile', UpdateProfileController::class)
        ->name('settings.profile.update');
    Route::put('settings/password', UpdatePasswordController::class)
        ->name('settings.password.update');

    // Создавать и править персонажей может только вошедший: у
    // персонажа должен быть автор.
    Route::post('characters', StoreCharacterController::class)
        ->name('characters.store');
    // Дописывание текста обращается к модели, поэтому с ограничением
    // частоты: кнопку нельзя превращать в бесплатный чат.
    Route::post('characters/write', WriteCharacterTextController::class)
        ->middleware('throttle:12,1')
        ->name('characters.write');
    Route::post('characters/{character}', UpdateCharacterController::class)
        ->whereNumber('character')
        ->name('characters.update');
    Route::delete('characters/{character}', DestroyCharacterController::class)
        ->whereNumber('character')
        ->name('characters.destroy');

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
