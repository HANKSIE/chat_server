<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class Group implements ShouldBroadcast
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var string
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function __construct($id, $message)
    {
        $this->id = $id;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PresenceChannel("group.{$this->id}");
    }

    public function broadcastAs()
    {
        return "message";
    }
}
