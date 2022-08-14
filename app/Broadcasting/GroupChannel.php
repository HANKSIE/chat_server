<?php

namespace App\Broadcasting;

use App\Models\User;
use App\Repositories\GroupRepository;

class GroupChannel
{
    private $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
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
        if ($this->groupRepository->has($user->id, $groupID)) {
            return $user;
        }
    }
}
