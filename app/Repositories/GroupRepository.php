<?php
namespace App\Repositories;

use App\Models\Group;
use App\Models\Message;
use App\Models\User;

class GroupRepository
{
    public function getAllIDs($userID)
    {
        return User::findOrFail($userID)->groups()->select('groups.id')->get()->map(function ($group) {
            return $group->id;
        });
    }

    public function join($userID, $groupID)
    {
        return Group::findOrFail($groupID)->members()->attach($userID);
    }

    public function has($userID, $groupID)
    {
        return User::findOrFail($userID)->groups()->where('groups.id', $groupID)->exists();
    }

    public function recentContactPaginate($userID, $isOneToOne, $perPage)
    {
        $subQuery = Message::selectRaw("MAX(messages.id)")
            ->whereHas('group.members', function ($query) use ($userID) {
                $query->where(['group_members.user_id' => $userID, 'group_members.deleted_at' => null]);
            })
            ->groupBy('group_id')
            ->latest('id');
        $mainQuery = Message::selectRaw('
            *,
            (
                SELECT CAST(
                    SUM(
                        CASE WHEN m.group_id = messages.group_id AND m.id >
                            (
                                CASE WHEN message_read.message_id IS NULL
                                THEN 0 ELSE message_read.message_id END
                            )
                        THEN 1 ELSE 0 END
                    ) AS INT
                )
                FROM messages AS m
                INNER JOIN message_read ON message_read.group_id = messages.group_id AND message_read.user_id = ?
            ) AS unread', [$userID]
        )
            ->whereIn('id', $subQuery)
            ->with(
                $isOneToOne ?
                [
                    'group.members' => function ($query) use ($userID) {
                        $query->where('group_members.user_id', '!=', $userID);
                    },
                ] : 'group'
            )
            ->latest('id');

        $paginate = $mainQuery
            ->cursorPaginate($perPage)
            ->withQueryString();

        $paginate->through(function ($dirtyMsg) {
            return [
                'message' => collect($dirtyMsg)->except('unread')->toArray(),
                'unread' => $dirtyMsg->unread,
            ];
        });

        return $paginate;
    }

    public function getIntersectionGroups($user1ID, $user2ID, $isOneToOne)
    {
        return User::findOrFail($user1ID)->groups()->when($isOneToOne, function ($query) {
            $query->oneToOne();
        }, function ($query) {
            $query->notOneToOne();
        })->whereHas('members', function ($query) use ($user2ID) {
            $query->where(['group_members.user_id' => $user2ID, 'group_members.deleted_at' => null]);
        })->get();
    }

    public function getMessageReads($groupID)
    {
        return Group::findOrFail($groupID)->messageReads;
    }
}
