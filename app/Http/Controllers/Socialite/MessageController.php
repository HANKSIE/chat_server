<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Services\MessageService;

class MessageController extends Controller
{
    private $messageService;
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    public function simplePaginate($groupID, $perPage = 5, $keyword = '')
    {
        return $this->messageService->simplePaginate($groupID, $keyword, $perPage);
    }
}
