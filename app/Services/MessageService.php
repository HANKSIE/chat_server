<?php
namespace App\Services;

use App\Events\Socialite\Message\MarkAsRead;
use App\Events\Socialite\Message\SendMessage;
use App\Repositories\MessageRepository;
use Illuminate\Support\Facades\Gate;

class MessageService
{
    private $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function createAndMarkAsRead($userID, $groupID, $body)
    {
        abort_if(Gate::denies('access-message', $groupID), 403, 'forbidden');

        $data = $this->messageRepository->createAndMarkAsRead($userID, $groupID, $body);
        $message = $data['message'];
        $record = $data['message_read'];

        if (!is_null($record)) {
            broadcast(new MarkAsRead($record));
        }

        broadcast(new SendMessage($message))->toOthers();
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
