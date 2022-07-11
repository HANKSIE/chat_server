<?php
namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FriendService
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function createFriendRequest($senderID, $recipientID)
    {
        return User::find($senderID)->friendRequestsFromMe()->firstOrCreate(['recipient_id' => $recipientID]);
    }

    public function denyFriendRequest($senderID, $recipientID)
    {
        return User::find($recipientID)->friendRequestsToMe()->where(['sender_id' => $senderID])->delete();
    }

    public function acceptFriendRequest($senderID, $recipientID)
    {
        $sender = User::find($senderID);
        $recipient = User::find($recipientID);
        $req = $recipient->friendRequestsToMe()->where(['sender_id' => $sender->id])->first();
        if (is_null($req)) {
            return false;
        }

        return DB::transaction(function () use ($sender, $recipient, $req) {
            $this->beFriend($sender->id, $recipient->id);
            $req->delete();
            return true;
        });
    }

    public function beFriend($senderID, $recipientID)
    {
        $sender = User::find($senderID);
        $recipient = User::find($recipientID);
        return DB::transaction(function () use ($sender, $recipient) {
            $group = Group::create(['is_one_to_one' => true]);
            $this->groupService->join($recipient->id, $group->id);
            $this->groupService->join($sender->id, $group->id);
            $sender->friends()->attach($recipient, ['group_id' => $group->id]);
            $recipient->friends()->attach($sender, ['group_id' => $group->id]);
        });
    }

    public function unFriend($senderID, $recipientID)
    {
        $sender = User::find($senderID);
        $recipient = User::find($recipientID);
        return DB::transaction(function () use ($sender, $recipient) {
            $sender->friends()->detach($recipient->id);
            $recipient->friends()->detach($sender->id);
        });
    }

    public function simplePaginate($userID, $keyword = '', $perPage = 5)
    {
        $friendIDs = User::find($userID)->friends->map(function ($friend) {
            return $friend->id;
        })->toArray();
        $simplePaginate = User::search($keyword)->whereIn('id', $friendIDs)->simplePaginate($perPage);
        $simplePaginate->load([
            'groups' => function ($query) use ($userID) {
                $query->oneToOne()->select('groups.id')->whereHas('members', function ($query) use ($userID) {
                    $query->where('user_id', $userID);
                });
            },
            'groups.latestMessage',
        ]);
        return tap($simplePaginate, function ($paginatedInstance) {
            return $paginatedInstance->getCollection()->transform(function ($user) {
                $group = $user->groups[0];
                $latestMessage = $group->latestMessage;
                unset($group->pivot);
                unset($group->latestMessage);
                unset($user->groups);
                return [
                    'user' => $user,
                    'group' => $group,
                    'latest_message' => $latestMessage,
                ];
            });
        });
    }

    public function getGroup($userID, $friendID)
    {
        return User::find($userID)->groups()->OneToOne()->whereHas('members', function ($query) use ($friendID) {
            $query->where('group_members.user_id', $friendID);
        })->first();
    }
}
