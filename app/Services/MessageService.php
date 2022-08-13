<?php
namespace App\Services;

use App\Events\Socialite\Message\MarkAsRead;
use App\Events\Socialite\Message\SendMessage;
use App\Models\Group;
use App\Models\MessageRead;
use Illuminate\Support\Facades\DB;

class MessageService
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function create($userID, $groupID, $body)
    {
        if (!$this->groupService->has($userID, $groupID)) {
            return null;
        }

        $message = DB::transaction(function () use ($groupID, $userID, $body) {
            $message = Group::find($groupID)->messages()->create(['body' => $body, 'user_id' => $userID]);
            return $message->load(
                $message->group->is_one_to_one ?
                [
                    'user',
                    'group.members',
                ] : 'user'
            );
        });

        broadcast(new SendMessage($message))->toOthers();
        $this->markAsRead($userID, $groupID);
        return $message;
    }

    public function paginate($groupID, $perPage = 5)
    {
        return Group::find($groupID)->messages()
            ->with('user')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function markAsRead($userID, $groupID)
    {
        $latestMessage = Group::find($groupID)->latestMessage;
        if (is_null($latestMessage)) {
            return;
        }
        $record = MessageRead::where(['user_id' => $userID, 'group_id' => $groupID])->first();
        $record->message_id = $latestMessage->id;
        $record->save();
        broadcast(new MarkAsRead($record->fresh()));
    }
}
