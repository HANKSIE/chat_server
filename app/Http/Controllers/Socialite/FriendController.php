<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Services\FriendService;

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
}
