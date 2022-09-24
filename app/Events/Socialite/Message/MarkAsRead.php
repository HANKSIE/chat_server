<?php

namespace App\Events\Socialite\Message;

use App\Models\MessageRead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\InteractsWithQueue;

class MarkAsRead implements ShouldBroadcast
{
    use InteractsWithSockets, InteractsWithQueue;

    /**
     * @var MessageRead
     */
    public $messageRead;

    public function __construct(MessageRead $messageRead)
    {
        $this->messageRead = $messageRead;
    }

    public function broadcastOn()
    {
        return new PresenceChannel("group.{$this->messageRead->group_id}");
    }

    public function broadcastAs()
    {
        return "mark-as-read";
    }
}
