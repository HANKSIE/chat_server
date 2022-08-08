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

    public function simplePaginate($groupID, $perPage = 5, $keyword = '')
    {
        return $this->messageService->simplePaginate($groupID, $keyword, $perPage);
    }

    public function cursorPaginate($groupID, $perPage = 5)
    {
        return $this->messageService->cursorPaginate($groupID, $perPage);
    }

    public function markAsRead($groupID)
    {
        $this->messageService->markAsRead(auth()->user()->id, $groupID);
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    public function messageReads($groupID)
    {
        return ['message_reads' => $this->messageService->messageReads($groupID)];
    }
}
