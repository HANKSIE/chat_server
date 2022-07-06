<?php
namespace App\Services;

use App\Models\Group;

class ChatService
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function create($userID, $groupID, $body)
    {
        if ($this->groupService->has($userID, $groupID)) {
            return Group::find($groupID)->messages()->create(['group_id' => $groupID, 'user_id' => $userID, 'body' => $body]);
        }
    }
}
