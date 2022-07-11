<?php
namespace App\Services;

use App\Models\Group;
use Illuminate\Support\Facades\DB;

class ChatService
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function create($userID, $groupID, $body)
    {
        if (!$this->groupService->has($userID, $groupID)) {
            return false;
        }

        return DB::transaction(function () use ($groupID, $userID, $body) {
            $message = Group::find($groupID)->messages()->create(['body' => $body]);
            $message->user()->associate($userID);
            $message->save();
            return $message->fresh();
        });
    }
}
