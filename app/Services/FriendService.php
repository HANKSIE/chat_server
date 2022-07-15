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
        $friendIDs = $this->getAllFriendIDs($userID);
        if (count($friendIDs) === 0) { // 回傳data為空的simple paginate ($friendsIDs為空search->whereIn會丟出exception)
            return User::find($userID)->friends()->simplePaginate($perPage);
        }

        $simplePaginate = User::search($keyword)->query(function ($query) use ($userID) {
            $query->with([
                'groups' => function ($query) use ($userID) {
                    $query->oneToOne()->with(['members' => function ($query) use ($userID) {
                        $query->where('user_id', $userID);
                    }]);
                },
            ]);
        })->whereIn('id', $friendIDs)->simplePaginate($perPage);
        return tap($simplePaginate, function ($paginatedInstance) {
            return $paginatedInstance->getCollection()->transform(function ($user) {
                $group = $user->groups[0];
                unset($group->pivot);
                unset($group->latestMessage);
                unset($user->groups);
                return [
                    'user' => $user,
                    'group_id' => $group->id,
                ];
            });
        });
    }

    public function usersSimplePaginate($userID, $keyword = '', $perPage = 5)
    {
        $friendIDs = collect($this->getAllFriendIDs($userID));
        return tap(User::search($keyword)->simplePaginate($perPage), function ($simplePaginate) use ($friendIDs, $userID) {
            return $simplePaginate->getCollection()->transform(function ($user) use ($friendIDs, $userID) {
                return [
                    'user' => $user,
                    'is_friend' => $friendIDs->contains($user->id),
                    'is_me' => $user->id === $userID,
                ];
            });
        });
    }

    private function getAllFriendIDs($userID)
    {
        return User::find($userID)->friends()->select('friends.friend_id')->get()->map(function ($data) {
            return $data->friend_id;
        })->toArray();
    }
}
