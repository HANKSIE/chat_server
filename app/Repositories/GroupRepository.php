<?php
namespace App\Repositories;

use App\Models\Group;
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
        $paginate = User::findOrFail($userID)->messageReads()
            ->with($isOneToOne ?
                [
                    'group.latestMessage.group.members' => function ($query) use ($userID) {
                        $query->where('group_members.user_id', '!=', $userID);
                    },
                ] : ['group', 'group.latestMessage.group'])
            ->whereRelation('group', 'is_one_to_one', $isOneToOne)
            ->has('group.latestMessage')
            ->whereHas('group.members', function ($query) use ($userID) {
                $query->where(['group_members.user_id' => $userID, 'group_members.deleted_at' => null]);
            })
            ->withAggregate('group', 'latest_message_id')
            ->orderByDesc('group_latest_message_id')
            ->orderByDesc('id')
            ->cursorPaginate($perPage)
            ->withQueryString();

        $paginate->through(function ($messageRead) {
            return [
                'message' => $messageRead->group->latestMessage,
                'unread' => $messageRead->unread,
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
