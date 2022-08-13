<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessageController extends Controller
{
    private $messageService;
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function store($groupID, Request $request)
    {
        $request->validate([
            'body' => ['required', 'string'],
        ]);
        $message = $this->messageService->create(auth()->user()->id, $groupID, $request->body);
        return response()->json(['message' => $message]);
    }

    public function paginate($groupID, $perPage = 5)
    {
        return $this->messageService->paginate($groupID, $perPage);
    }

    public function markAsRead($groupID)
    {
        $this->messageService->markAsRead(auth()->user()->id, $groupID);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
