<?php

namespace App\Events\Socialite\Message;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\InteractsWithQueue;

class SendMessage implements ShouldBroadcast
{
    use InteractsWithSockets, InteractsWithQueue;

    /**
     * @var Message
     */
    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PresenceChannel("group.{$this->message->group_id}");
    }

    public function broadcastAs()
    {
        return "message";
    }
}
