<?php
namespace App\Repositories;

use App\Models\Group;
use App\Models\MessageRead;

class MessageRepository
{

    public function create($userID, $groupID, $body)
    {
        $message = Group::findOrFail($groupID)->messages()->create(['body' => $body, 'user_id' => $userID]);
        return $message->load(
            $message->group->is_one_to_one ?
            [
                'user',
                'group.members',
            ] : 'user'
        );
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
        $record = MessageRead::where(['user_id' => $userID, 'group_id' => $groupID])->firstOrFail();
        $record->message_id = $latestMessage->id;
        $record->save();
        return $record->fresh();
    }
}
