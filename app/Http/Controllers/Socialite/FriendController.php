<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
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
        $userID = auth()->user()->id;
        $recipientID = $request->recipient_id;
        if ($this->friendService->hasRequest($recipientID, $userID)) {
            $group = $this->friendService->acceptFriendRequest($recipientID, $userID);
            return response()->json(['be_friend' => true, 'group_id' => $group->id]);
        }
        $this->friendService->createFriendRequest(auth()->user()->id, $request->recipient_id);
        return response()->json(['be_friend' => false]);
    }

    public function acceptRequest(Request $request)
    {
        $userID = auth()->user()->id;
        $senderID = $request->sender_id;
        $group = $this->friendService->acceptFriendRequest($senderID, $userID);
        return response()->json(['group_id' => $group->id]);
    }

    public function denyRequest(Request $request)
    {
        $this->friendService->denyFriendRequest($request->sender_id, auth()->user()->id);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function unfriend(Request $request)
    {
        // broadcast for echo leave
        $this->friendService->unfriend(auth()->user()->id, $request->friend_id);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function requestsToMe($perPage)
    {
        return $this->friendService->requestsToMeCursorPaginate(auth()->user()->id, $perPage);
    }

    public function requestsFromMe($perPage)
    {
        return $this->friendService->requestsFromMeCursorPaginate(auth()->user()->id, $perPage);
    }
}
