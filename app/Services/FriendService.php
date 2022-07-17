<?php
namespace App\Services;

use App\Events\BeFriend;
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

        $group = DB::transaction(function () use ($sender, $recipient, $req) {
            $group = $this->beFriend($sender->id, $recipient->id);
            $sender = $req->sender;
            $req->delete();
            return $group;
        });

        broadcast(new BeFriend($senderID, $recipientID, $group->id))->toOthers();
        return $group;
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
            return $group;
        });
    }

    public function unFriend($user1ID, $user2ID)
    {
        $user1 = User::find($user1ID);
        $user2 = User::find($user2ID);
        return DB::transaction(function () use ($user1, $user2) {
            $user1->friends()->detach($user2->id);
            $user2->friends()->detach($user1->id);
            $this->groupService->getOneToOneGroup($user1->id, $user2->id)->delete();
        });
    }

    public function simplePaginate($userID, $keyword = '', $perPage = 5)
    {
        $friendIDs = $this->getAllFriendIDs($userID);
        if (count($friendIDs) === 0) { // 回傳data為空的simple paginate ($friendsIDs為空search->whereIn會丟出exception)
            return User::find($userID)->friends()->simplePaginate($perPage);
        }

        $simplePaginate = User::search($keyword)->whereIn('id', $friendIDs)->simplePaginate($perPage);

        $simplePaginate->load(['groups' => function ($query) use ($userID) {
            $query->oneToOne()->whereHas('members', function ($query) use ($userID) {
                $query->where('user_id', $userID);
            });
        }]);

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
        $simplePaginate = User::search($keyword)->simplePaginate($perPage);
        $ids = $simplePaginate->getCollection()->map(function ($user) {return $user->id;});
        $statuses = DB::table('users')->selectRaw("(CASE
            WHEN users.id = ? THEN 1
            WHEN EXISTS(SELECT id FROM friends WHERE friends.user_id = ? AND friends.friend_id = users.id)
            THEN 2
            WHEN EXISTS(SELECT id FROM friend_requests WHERE friend_requests.sender_id = users.id AND friend_requests.recipient_id = ?)
            THEN 3
            WHEN EXISTS(SELECT id FROM friend_requests WHERE friend_requests.sender_id = ? AND friend_requests.recipient_id = users.id)
            THEN 4
            ELSE 0
            END) AS status
        ",
            collect()->range(1, 4)->map(function () use ($userID) {
                return $userID;
            })->toArray()
        )->whereIn('id', $ids)->orderBy('id')->get()->map(function ($data) {
            return $data->status;
        });
        return tap($simplePaginate, function ($simplePaginate) use ($statuses) {
            return $simplePaginate->getCollection()->transform(function ($user, $i) use ($statuses) {
                return [
                    'user' => $user,
                    'status' => $statuses[$i],
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

    public function hasRequest($senderID, $recipientID)
    {
        return User::find($senderID)->friendRequestsFromMe()->where('recipient_id', $recipientID)->exists();
    }
}
