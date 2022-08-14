<?php
namespace App\Services;

use App\Events\Socialite\Friend\BeFriend;
use App\Events\Socialite\Friend\UnFriend;
use App\Repositories\FriendRepository;

class FriendService
{
    private $friendRepository;

    public function __construct(FriendRepository $friendRepository)
    {
        $this->friendRepository = $friendRepository;
    }

    public function createRequest($senderID, $recipientID)
    {
        if ($this->friendRepository->hasRequest($recipientID, $senderID)) {
            $group = $this->acceptRequest($recipientID, $senderID);
            return ['be_friend' => true, 'group_id' => $group->id];
        }

        $this->friendRepository->createRequest($senderID, $recipientID);
        return ['be_friend' => false];
    }

    public function denyRequest($senderID, $recipientID)
    {
        return $this->friendRepository->denyRequest($senderID, $recipientID);
    }

    public function acceptRequest($senderID, $recipientID)
    {
        $group = $this->friendRepository->acceptRequest($senderID, $recipientID);
        if (!is_null($group)) {
            broadcast(new BeFriend($senderID, $recipientID, $group->id))->toOthers();
        }
        return $group;
    }

    public function unFriend($user1ID, $user2ID)
    {
        $group = $this->friendRepository->unFriend($user1ID, $user2ID);
        if (!is_null($group)) {
            broadcast(new UnFriend($user1ID, $user2ID, $group->id))->toOthers();
        }
    }

    public function paginate($userID, $keyword, $perPage)
    {
        return $this->friendRepository->paginate($userID, $keyword, $perPage);
    }

    public function findNewFriendPaginate($userID, $keyword, $perPage)
    {
        return $this->friendRepository->findNewFriendPaginate($userID, $keyword, $perPage);
    }

    public function requestsPaginate($userID, $type, $perPage)
    {
        return $this->friendRepository->requestsPaginate($userID, $type, $perPage);
    }
}
