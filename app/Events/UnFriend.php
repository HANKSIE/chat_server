<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UnFriend implements ShouldBroadcast
{
    use InteractsWithSockets;

    private $userID;
    private $friendID;
    public $group_id;

    public function __construct($userID, $friendID, $group_id)
    {
        $this->userID = $userID;
        $this->friendID = $friendID;
        $this->group_id = $group_id;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("user.{$this->userID}"),
            new PrivateChannel("user.{$this->friendID}"),
        ];
    }

    public function broadcastAs()
    {
        return "unfriend";
    }
}
