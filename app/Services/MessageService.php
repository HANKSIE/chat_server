<?php
namespace App\Services;

use App\Events\GroupMessage;
use App\Models\Group;
use App\Models\Message;
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
            $this->markAsRead($userID, $groupID);
            return $message->load(
                $message->group->is_one_to_one ?
                [
                    'user',
                    'group.members',
                ] : 'user'
            );
        });

        broadcast(new GroupMessage($message))->toOthers();
        return $message;
    }

    public function simplePaginate($groupID, $keyword = '', $perPage = 5)
    {
        $simplePaginate = Message::search($keyword)
            ->where('group_id', $groupID)
            ->query(function ($query) {
                $query->with('user');
            })
            ->orderBy('id', 'desc')
            ->simplePaginate($perPage);
        return $simplePaginate;
    }

    public function cursorPaginate($groupID, $perPage = 5)
    {
        return Group::find($groupID)->messages()
            ->with('user')
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);
    }

    public function messageReads($groupID)
    {
        return Group::find($groupID)->messageReads;
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
    }
}
