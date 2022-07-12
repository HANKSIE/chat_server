<?php

use App\Http\Controllers\Socialite\FriendController;
use App\Http\Controllers\Socialite\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('friends/{perPage}/{keyword?}', [FriendController::class, 'simplePaginate'])
        ->name('friend.simple-paginate');
    Route::get('messages/{groupID}/{perPage?}/{keyword?}', [MessageController::class, 'simplePaginate'])
        ->name('message.simple-paginate');
});
