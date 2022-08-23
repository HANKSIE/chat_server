<?php

use App\Http\Controllers\Socialite\FriendController;
use App\Http\Controllers\Socialite\GroupController;
use App\Http\Controllers\Socialite\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('friends')->group(function () {
        Route::get('search', [FriendController::class, 'paginate']);
        Route::delete('', [FriendController::class, 'unfriend'])->name('unfriend');
        Route::prefix('request')->group(function () {
            Route::post('send', [FriendController::class, 'sendRequest'])->name('friend.request.send');
            Route::post('accept', [FriendController::class, 'acceptRequest'])->name('friend.request.accept');
            Route::post('deny', [FriendController::class, 'denyRequest'])->name('friend.request.deny');
            Route::delete('revoke', [FriendController::class, 'revokeRequest'])->name('friend.request.revoke');
        });
        Route::prefix('requests')->group(function () {
            Route::get('search', [FriendController::class, 'requestsPaginate']);
        });
    });

    Route::prefix('group/{groupID}')->middleware(['can:access-group,groupID'])->group(function () {
        Route::prefix('messages')->group(function () {
            Route::get('search', [MessageController::class, 'paginate'])
                ->name('message.paginate');
        });
        Route::prefix('message')->group(function () {
            Route::put('mark-as-read', [MessageController::class, 'markAsRead'])
                ->name('message.mark-as-read');
        });
        Route::resource('messages', MessageController::class)->only('store');
        Route::get('message-reads', [GroupController::class, 'messageReads']);
    });

    Route::prefix('groups')->group(function () {
        Route::get('recent-contact/search', [GroupController::class, 'recentContactPaginate'])
            ->name('group.recent-contact.paginate');
    });

    Route::resource('groups', GroupController::class)->only(['index']);
    Route::get('users/search', [FriendController::class, 'findNewFriendPaginate']);
});
