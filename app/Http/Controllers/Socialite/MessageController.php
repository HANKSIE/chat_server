<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private $messageService;
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'group_id' => ['required', 'numeric'],
            'body' => ['required', 'string'],
        ]);
        $message = $this->messageService->create(auth()->user()->id, $request->group_id, $request->body);
        return response()->json(['message' => $message]);
    }

    public function simplePaginate($groupID, $perPage = 5, $keyword = '')
    {
        return $this->messageService->simplePaginate($groupID, $keyword, $perPage);
    }
}
