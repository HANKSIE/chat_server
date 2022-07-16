<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FriendService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FriendController extends Controller
{
    private $friendService;

    public function __construct(FriendService $friendService)
    {
        $this->friendService = $friendService;
    }

    public function simplePaginate($perPage = 5, $keyword = '')
    {
        return response()->json($this->friendService->simplePaginate(auth()->user()->id, $keyword, $perPage));
    }

    public function usersSimplePaginate($perPage = 5, $keyword = '')
    {
        return response()->json($this->friendService->usersSimplePaginate(auth()->user()->id, $keyword, $perPage));
    }

    public function sendRequest(Request $request)
    {
        return response()->json(['recipient' => $this->friendService->createFriendRequest(auth()->user()->id, $request->recipient_id)]);
    }

    public function acceptRequest(Request $request)
    {
        // broadcast for echo join
        return response()->json(['sender' => $this->friendService->acceptFriendRequest($request->sender_id, auth()->user()->id)]);
    }

    public function denyRequest(Request $request)
    {
        return response()->json(['sender' => response()->json($this->friendService->denyFriendRequest($request->sender_id, auth()->user()->id))]);
    }

    public function unfriend(Request $request)
    {
        // broadcast for echo leave
        $this->friendService->unfriend(auth()->user()->id, $request->friend_id);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
