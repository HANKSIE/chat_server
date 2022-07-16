<?php

namespace App\Broadcasting;

use App\Models\User;

class UserChannel
{
    public function join(User $user, $userID)
    {
        return $user->id == $userID;
    }
}
