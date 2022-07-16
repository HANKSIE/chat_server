<?php

use App\Broadcasting\GroupChannel;
use App\Broadcasting\UserChannel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
 */

Broadcast::channel('group.{groupID}', GroupChannel::class);

Broadcast::channel('user.{userID}', UserChannel::class);
