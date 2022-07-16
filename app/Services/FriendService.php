<?php
namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Ds\Set;
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
        $req = User::find($senderID)->friendRequestsFromMe()->firstOrCreate(['recipient_id' => $recipientID]);
        return $req->recipient;
    }

    public function denyFriendRequest($senderID, $recipientID)
    {
        $req = User::find($recipientID)->friendRequestsToMe()->where(['sender_id' => $senderID])->first();
        $sender = $req->sender;
        $req->delete();
        return $sender;
    }

    public function acceptFriendRequest($senderID, $recipientID)
    {
        $sender = User::find($senderID);
        $recipient = User::find($recipientID);
        $req = $recipient->friendRequestsToMe()->where(['sender_id' => $sender->id])->first();
        if (is_null($req)) {
            return null;
        }

        return DB::transaction(function () use ($sender, $recipient, $req) {
            $this->beFriend($sender->id, $recipient->id);
            $sender = $req->sender;
            $req->delete();
            return $sender;
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
        $senderIDs = new Set(User::find($userID)->friendRequestsToMe->map(function ($req) {return $req->sender_id;}));
        $recipientIDs = new Set(User::find($userID)->friendRequestsFromMe->map(function ($req) {return $req->recipient_id;}));
        $friendIDs = new Set($this->getAllFriendIDs($userID));

        return tap(User::search($keyword)->simplePaginate($perPage), function ($simplePaginate) use ($friendIDs, $senderIDs, $recipientIDs, $userID) {
            return $simplePaginate->getCollection()->transform(function ($user) use ($friendIDs, $senderIDs, $recipientIDs, $userID) {
                $status = 0; // stranger
                if ($user->id === $userID) { // me
                    $status = 1;
                } else if ($friendIDs->contains($user->id)) { // friend
                    $status = 2;
                } else if ($senderIDs->contains($user->id)) { // inviting the user
                    $status = 3;
                } else if ($recipientIDs->contains($user->id)) { // the user invite me
                    $status = 4;
                }
                return [
                    'user' => $user,
                    'status' => $status,
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
