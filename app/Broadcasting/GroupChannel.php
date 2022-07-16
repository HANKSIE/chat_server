<?php

namespace App\Broadcasting;

use App\Models\User;
use App\Services\GroupService;

class GroupChannel
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\Models\User  $user
     * @param  int  $groupID
     * @return array|bool
     */
    public function join(User $user, $groupID)
    {
        if ($this->groupService->has($user->id, $groupID)) {
            return $user;
        }
    }
}
