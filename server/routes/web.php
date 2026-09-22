<?php

use App\Http\Controllers\Chat\DestroyChatController;
use App\Http\Controllers\Chat\IndexChatController;
use App\Http\Controllers\Chat\ShowChatController;
use App\Http\Controllers\Chat\StoreChatController;
use App\Http\Controllers\Chat\StoreMessageController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('chats', IndexChatController::class)->name('chats.index');
    Route::post('chats', StoreChatController::class)->name('chats.store');
    Route::get('chats/{chat}', ShowChatController::class)->name('chats.show');
    Route::delete('chats/{chat}', DestroyChatController::class)->name('chats.destroy');

    Route::post('chats/{chat}/messages', StoreMessageController::class)
        ->name('chats.messages.store');
});

require __DIR__.'/auth.php';
