<?php
namespace App\Repositories;

use App\Models\Group;
use App\Models\MessageRead;
use Illuminate\Support\Facades\DB;

class MessageRepository
{

    public function create($userID, $groupID, $body)
    {
        $message = DB::transaction(function () use ($userID, $groupID, $body) {
            $message = Group::findOrFail($groupID)->messages()->create(['body' => $body, 'user_id' => $userID]);
            MessageRead::where(['group_id' => $groupID])
                ->increment('unread', 1, ['latest_message_id' => $message->id]);

            return $message;
        });

        return $message->load(
            $message->group->is_one_to_one ?
            [
                'user',
                'group.members',
            ] : 'user'
        );
    }

    public function createAndMarkAsRead($userID, $groupID, $body)
    {
        return DB::transaction(function () use ($userID, $groupID, $body) {
            $message = $this->create($userID, $groupID, $body);
            $messageRead = null;

            if (!is_null($userID)) {
                $messageRead = $this->markAsRead($userID, $groupID);
            }

            return [
                'message' => $message,
                'message_read' => $messageRead,
            ];
        });
    }

    public function paginate($groupID, $perPage)
    {
        return Group::findOrFail($groupID)
            ->messages()
            ->with('user')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function markAsRead($userID, $groupID)
    {
        $latestMessage = Group::findOrFail($groupID)->latestMessage;
        if (is_null($latestMessage)) {
            return null;
        }
        $record = MessageRead::where(['user_id' => $userID, 'group_id' => $groupID])->firstOrFail();
        $record->message_id = $latestMessage->id;
        $record->unread = 0;
        $record->save();
        return $record->fresh();
    }
}
