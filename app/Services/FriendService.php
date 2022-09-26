<?php
namespace App\Services;

use App\Events\Socialite\Friend\BeFriend;
use App\Events\Socialite\Friend\UnFriend;
use App\Models\User;
use App\Repositories\FriendRepository;
use App\Repositories\GroupRepository;
use Illuminate\Support\Facades\DB;

class FriendService
{
    private $friendRepository;
    private $groupRepository;
    private $messageService;

    public function __construct(FriendRepository $friendRepository, MessageService $messageService, GroupRepository $groupRepository)
    {
        $this->friendRepository = $friendRepository;
        $this->messageService = $messageService;
        $this->groupRepository = $groupRepository;
    }

    public function createRequest($senderID, $recipientID)
    {
        abort_if($senderID === $recipientID, 422, '$senderID cannot be equal to $recipientID.');
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
        broadcast(new BeFriend($senderID, $recipientID, $group->id))->toOthers();
        return $group;
    }

    public function unFriend($user1ID, $user2ID)
    {
        $groups = $this->groupRepository->getIntersectionGroups($user1ID, $user2ID, true);
        if (count($groups) === 0) {
            return;
        }

        $group = $groups[0];

        if (!is_null($group)) {
            $user1Name = User::findOrFail($user1ID)->name;
            DB::transaction(function () use ($group, $user1ID, $user2ID, $user1Name) {
                $this->messageService->createAndMarkAsRead(null, $group->id, "$user1Name 已離開群組");
                $this->friendRepository->unFriend($user1ID, $user2ID);
            });
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
