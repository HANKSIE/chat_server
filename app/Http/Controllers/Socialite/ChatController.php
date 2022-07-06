<?php

namespace App\Http\Controllers\Socialite;

use App\Events\GroupMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ChatController extends Controller
{
    public function sendMessage($groupID, $body)
    {
        broadcast(new GroupMessage($groupID, $body));
        return response('', Response::HTTP_NO_CONTENT);
    }
}
