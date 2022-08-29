<?php
namespace App\Repositories;

use App\Models\FriendRequest;
use App\Models\Group;
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
        $req = User::find($senderID)->friendRequestsFromMe()->firstOrCreate(['recipient_id' => $recipientID]);
        return $req;
    }

    public function denyRequest($senderID, $recipientID)
    {
        return User::find($recipientID)->friendRequestsToMe()->where(['sender_id' => $senderID])->delete();
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
        return User::find($senderID)->friendRequestsFromMe()->where('recipient_id', $recipientID)->exists();
    }

    private function beFriend($senderID, $recipientID)
    {
        $sender = User::find($senderID);
        $recipient = User::find($recipientID);
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
        $user1 = User::find($user1ID);
        $user2 = User::find($user2ID);
        $groups = $this->groupRepository->getIntersectionGroups($user1->id, $user2->id, true);
        if (count($groups) !== 0) {
            $group = $groups[0];
            DB::transaction(function () use ($user1, $user2, $group) {
                $user1->friends()->detach($user2->id);
                $user2->friends()->detach($user1->id);
                $group->delete();
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

        if (strlen($keyword) === 0) {
            $paginate = User::find($userID)->friends()->with('groups', function ($query) use ($setIntersectGroupQuery) {
                $setIntersectGroupQuery($query);
            })->cursorPaginate($perPage)->withQueryString();
        } else {
            $friendIDs = $this->getAllIDs($userID);
            $paginate = User::search($keyword)
                ->when(!empty($friendIDs), function ($query) use ($friendIDs) {
                    $query->whereIn('id', $friendIDs);
                })
                ->query(function ($query) use ($setIntersectGroupQuery) {
                    $query->with('groups', function ($query) use ($setIntersectGroupQuery) {
                        $setIntersectGroupQuery($query);
                    });
                })
                ->simplePaginate($perPage)->withQueryString();
        }

        $paginate->{strlen($keyword) === 0 ? 'throughWhenSerialize' : 'through'}(function ($user) {
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
        $paginate = strlen($keyword) === 0 ?
        User::cursorPaginate($perPage)->withQueryString() :
        User::search($keyword)->simplePaginate($perPage)->withQueryString();

        $ids = $paginate->getCollection()->map(function ($user) {return $user->id;});

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
        )->whereIn('id', $ids)->orderBy('id')->get()->map(function ($data) {
            return $data->state;
        });

        $paginate->{strlen($keyword) === 0 ? 'throughWhenSerialize' : 'through'}(function ($user, $i) use ($states) {
            return [
                'user' => $user,
                'state' => $states[$i],
            ];
        });

        return $paginate;
    }

    private function getAllIDs($userID)
    {
        return User::find($userID)->friends()->select('friends.friend_id')->get()->map(function ($data) {
            return $data->friend_id;
        })->toArray();
    }

    public function requestsPaginate($userID, $type, $perPage)
    {
        return tap(User::find($userID)
                ->{$type == 'receive' ? "friendRequestsToMe" : "friendRequestsFromMe"}()
                ->simplePaginate($perPage)
                ->withQueryString(), function ($paginate) use ($type) {
                $paginate->through(function ($req) use ($type) {
                    return $req->{$type == 'receive' ? 'sender' : 'recipient'};
                });
            });
    }
}
