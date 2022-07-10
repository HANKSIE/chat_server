<?php

use App\Http\Controllers\Socialite\FriendController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('friend')->group(function () {
    Route::get('search/{perPage}/{keyword?}', [FriendController::class, 'simplePaginate'])
        ->name('friend.simple-paginate');
});
