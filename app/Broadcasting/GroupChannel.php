<?php

namespace App\Broadcasting;

use App\Models\User;

class GroupChannel
{
    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\Models\User  $user
     * @return array|bool
     */
    public function join(User $user, $groupID)
    {
        if ($user->hasGroup($groupID)) {
            return $user;
        }
    }
}
