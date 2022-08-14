<?php
namespace App\Services;

use App\Events\Socialite\Message\MarkAsRead;
use App\Events\Socialite\Message\SendMessage;
use App\Repositories\MessageRepository;

class MessageService
{
    private $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function create($userID, $groupID, $body)
    {
        $message = $this->messageRepository->create($userID, $groupID, $body);
        broadcast(new SendMessage($message))->toOthers();
        $this->markAsRead($userID, $groupID);
        return $message;
    }

    public function paginate($groupID, $perPage)
    {
        return $this->messageRepository->paginate($groupID, $perPage);
    }

    public function markAsRead($userID, $groupID)
    {
        $record = $this->messageRepository->markAsRead($userID, $groupID);
        if (!is_null($record)) {
            broadcast(new MarkAsRead($record));
        }
    }
}
