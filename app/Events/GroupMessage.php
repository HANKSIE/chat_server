<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GroupMessage implements ShouldBroadcast
{
    /**
     * @var int|string
     */
    public $groupID;

    /**
     * @var string
     */
    public $body;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function __construct($groupID, $body)
    {
        $this->groupID = $groupID;
        $this->body = $body;
    }

    public function broadcastOn()
    {
        return new PresenceChannel("group.{$this->groupID}");
    }

    public function broadcastAs()
    {
        return "message";
    }
}
