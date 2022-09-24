<?php
namespace App\Repositories;

use App\Models\FriendRequest;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FriendRepository
{
    private $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function createRequest($senderID, $recipientID)
    {
        $req = User::findOrFail($senderID)->friendRequestsFromMe()->firstOrCreate(['recipient_id' => $recipientID]);
        return $req;
    }

    public function denyRequest($senderID, $recipientID)
    {
        return User::findOrFail($recipientID)->friendRequestsToMe()->where(['sender_id' => $senderID])->delete();
    }

    public function acceptRequest($senderID, $recipientID)
    {
        $req = FriendRequest::where(['sender_id' => $senderID, 'recipient_id' => $recipientID])->first();
        if (is_null($req)) {
            return null;
        }

        return DB::transaction(function () use ($senderID, $recipientID, $req) {
            $group = $this->beFriend($senderID, $recipientID);
            $req->delete();
            return $group;
        });
    }

    public function hasRequest($senderID, $recipientID)
    {
        return User::findOrFail($senderID)->friendRequestsFromMe()->where('recipient_id', $recipientID)->exists();
    }

    private function beFriend($senderID, $recipientID)
    {
        $sender = User::findOrFail($senderID);
        $recipient = User::findOrFail($recipientID);
        return DB::transaction(function () use ($sender, $recipient) {
            $group = Group::create(['is_one_to_one' => true]);
            $this->groupRepository->join($recipient->id, $group->id);
            $this->groupRepository->join($sender->id, $group->id);
            MessageRead::firstOrCreate(['user_id' => $sender->id, 'group_id' => $group->id]);
            MessageRead::firstOrCreate(['user_id' => $recipient->id, 'group_id' => $group->id]);
            $sender->friends()->attach($recipient, ['group_id' => $group->id]);
            $recipient->friends()->attach($sender, ['group_id' => $group->id]);
            return $group;
        });
    }

    public function unFriend($user1ID, $user2ID)
    {
        $user1 = User::findOrFail($user1ID);
        $user2 = User::findOrFail($user2ID);
        $groups = $this->groupRepository->getIntersectionGroups($user1->id, $user2->id, true);
        if (count($groups) !== 0) {
            $group = $groups[0];
            $record1 = GroupMember::where(['user_id' => $user1->id, 'group_id' => $group->id])->firstOrFail();
            $record2 = GroupMember::where(['user_id' => $user2->id, 'group_id' => $group->id])->firstOrFail();
            DB::transaction(function () use ($user1, $user2, $record1, $record2) {
                $user1->friends()->detach($user2->id);
                $user2->friends()->detach($user1->id);
                $record1->delete();
                $record2->delete();
            });
            return $group;
        }
        return null;
    }

    public function paginate($userID, $keyword, $perPage)
    {
        $paginate = null;

        $setIntersectGroupQuery = function ($query) use ($userID) {
            $query->oneToOne()->whereHas('members', function ($query) use ($userID) {
                $query->where('user_id', $userID);
            });
        };
        $friendIDs = $this->getAllIDs($userID);
        $paginate = (strlen($keyword) === 0 || empty($friendIDs) ? User::findOrFail($userID)->friends()->with('groups', function ($query) use ($setIntersectGroupQuery) {
            $setIntersectGroupQuery($query);
        })->cursorPaginate($perPage) :
            User::search($keyword)->whereIn('id', $friendIDs)
                ->query(function ($query) use ($setIntersectGroupQuery) {
                    $query->with('groups', function ($query) use ($setIntersectGroupQuery) {
                        $setIntersectGroupQuery($query);
                    });
                })
                ->simplePaginate($perPage))->withQueryString();

        $paginate->through(function ($user) {
            $group = $user->groups[0];
            unset($group->pivot);
            unset($group->latestMessage);
            unset($user->groups);
            return [
                'user' => $user,
                'group_id' => $group->id,
            ];
        });

        return $paginate;
    }

    public function findNewFriendPaginate($userID, $keyword, $perPage)
    {
        $paginate = (strlen($keyword) === 0 ?
            User::cursorPaginate($perPage) :
            User::search($keyword)->simplePaginate($perPage)
        )->withQueryString();

        $ids = $paginate->getCollection()->map(function ($user) {return $user->id;})->sort()->values();
        $states = DB::table('users')->selectRaw("(CASE
            WHEN users.id = ? THEN 1
            WHEN EXISTS(SELECT id FROM friends WHERE friends.user_id = ? AND friends.friend_id = users.id)
            THEN 2
            WHEN EXISTS(SELECT id FROM friend_requests WHERE friend_requests.sender_id = users.id AND friend_requests.recipient_id = ?)
            THEN 3
            WHEN EXISTS(SELECT id FROM friend_requests WHERE friend_requests.sender_id = ? AND friend_requests.recipient_id = users.id)
            THEN 4
            ELSE 0
            END) AS state
        ",
            collect()->range(1, 4)->map(function () use ($userID) {
                return $userID;
            })->toArray()
        )->whereIn('id', $ids)->get()->map(function ($data) {
            return $data->state;
        });

        $stateMap = [];
        $states->each(function ($state, $i) use (&$stateMap, $ids) {
            $stateMap[$ids[$i]] = $state;
        });

        $paginate->through(function ($user) use ($stateMap) {
            return [
                'user' => $user,
                'state' => $stateMap[$user->id],
            ];
        });

        return $paginate;
    }

    private function getAllIDs($userID)
    {
        return User::findOrFail($userID)->friends()->select('friends.friend_id')->get()->map(function ($data) {
            return $data->friend_id;
        })->toArray();
    }

    public function requestsPaginate($userID, $type, $perPage)
    {
        return tap(User::findOrFail($userID)
                ->{$type == 'receive' ? "friendRequestsToMe" : "friendRequestsFromMe"}()
                ->simplePaginate($perPage)
                ->withQueryString(), function ($paginate) use ($type) {
                $paginate->through(function ($req) use ($type) {
                    return $req->{$type == 'receive' ? 'sender' : 'recipient'};
                });
            });
    }
}
