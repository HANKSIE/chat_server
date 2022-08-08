<?php
namespace App\Services;

use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GroupService
{
    public function getAllIDs($userID)
    {
        return User::find($userID)->groups()->select('groups.id')->get()->map(function ($group) {
            return $group->id;
        });
    }

    public function join($userID, $groupID)
    {
        return Group::find($groupID)->members()->attach($userID);
    }

    public function has($userID, $groupID)
    {
        return User::find($userID)->groups()->where('groups.id', $groupID)->exists();
    }

    public function recentContactCursorPaginate($userID, $isOneToOne = false, $perPage = 5)
    {
        $data = DB::table('groups')
            ->selectRaw(
                "MAX(messages.id) AS mid,
                messages.group_id AS gid,
                (
                    SELECT CAST(
                        SUM(
                            CASE WHEN messages.group_id = gid AND messages.id >
                                (
                                    CASE WHEN message_read.message_id IS NULL
                                    THEN 0 ELSE message_read.message_id END
                                )
                            THEN 1 ELSE 0 END
                        ) AS INT
                    ) AS unread
                    FROM messages
                    INNER JOIN message_read ON message_read.group_id = gid AND message_read.user_id = ?
                ) AS unread
                ")
            ->setBindings([$userID])
            ->join('group_members', 'group_members.group_id', '=', 'groups.id')
            ->join('messages', 'messages.group_id', '=', 'groups.id')
            ->where([
                'group_members.user_id' => $userID,
                'groups.is_one_to_one' => $isOneToOne,
                'groups.deleted_at' => null,
            ])
            ->groupBy('groups.id')
            ->orderByDesc('mid')
            ->get();

        $mids = $data->map(function ($data) {return $data->mid;});
        $unreads = $data->map(function ($data) {return $data->unread;});

        $paginate = Message::whereIn('id', $mids)->with(
            $isOneToOne ?
            [
                'group.members' => function ($query) use ($userID) {
                    $query->where('group_members.user_id', '!=', $userID);
                },
            ] : 'group'
        )->latest('id')->simplePaginate($perPage);

        return tap($paginate, function ($paginate) use ($unreads) {
            return $paginate->getCollection()->transform(function ($message, $i) use ($unreads) {
                return [
                    'message' => $message,
                    'unread' => $unreads[$i],
                ];
            });
        });
    }

    public function getOneToOneGroup($user1ID, $user2ID)
    {
        return User::find($user1ID)->groups()->oneToOne()->whereHas('members', function ($query) use ($user2ID) {
            $query->where('user_id', $user2ID);
        })->first();
    }

    // public function create($userID, $name)
    // {
    //     return DB::transaction(function () use ($userID, $name) {
    //         $group = Group::create(['name' => $name, 'is_one_to_one' => false]);
    //         $this->join($userID, $group->id);
    //         return $group;
    //     });
    // }

    // public function leave($userID, $groupID)
    // {
    //     return DB::transaction(function () use ($userID, $groupID) {
    //         $group = Group::find($groupID);
    //         $result = $group->members()->detach($userID);
    //         if (!$group->members()->exists()) {
    //             $group->delete();
    //         }
    //         return $result;
    //     });
    // }

    // public function createRequest($senderID, $recipientID, $groupID)
    // {
    //     $group = User::find($senderID)->groups()->notOneToOne()->find($groupID);
    //     if (is_null($group) || !is_null($group->members()->find($recipientID))) {
    //         return false;
    //     }

    //     return $group->requests()->firstOrCreate(['sender_id' => $this->id, 'recipient_id' => $recipientID]);
    // }

    // public function denyRequest($senderID, $recipientID, $groupID)
    // {
    //     return User::find($recipientID)->groupRequestsToMe()->where(['group_id' => $groupID, 'sender_id' => $senderID])->delete();
    // }

    // public function acceptRequest($recipientID, $groupID)
    // {
    //     $recipient = User::find($recipientID);
    //     $group = Group::notOneToOne()->find($groupID);
    //     if (!$recipient->groupRequestsToMe()->where('group_id', $groupID)->exists() || is_null($group)) {
    //         return false;
    //     }
    //     return DB::transaction(function () use ($group, $recipient) {
    //         $this->join($recipient->id, $group->id);
    //         $recipient->groupRequestsToMe()->where('group_id', $group->id)->delete();
    //         return true;
    //     });
    // }
}
