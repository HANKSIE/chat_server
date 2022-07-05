<?php
namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GroupService
{
    public function getAllIDs($userID)
    {
        return User::find($userID)->groups()->select('groups.id')->get()->map(function ($group) {
            return $group->id;
        });
    }

    public function create($userID, $name)
    {
        return DB::transaction(function () use ($userID, $name) {
            $group = Group::create(['name' => $name, 'is_one_to_one' => false]);
            $this->join($userID, $group->id);
            return $group;
        });
    }

    public function join($userID, $groupID)
    {
        return Group::find($groupID)->members()->attach($userID);
    }

    public function leave($userID, $groupID)
    {
        return DB::transaction(function () use ($userID, $groupID) {
            $group = Group::find($groupID);
            $result = $group->members()->detach($userID);
            if (!$group->members()->exists()) {
                $group->delete();
            }
            return $result;
        });
    }

    public function has($userID, $groupID)
    {
        return User::find($userID)->groups()->find($groupID)->exists();
    }

    public function createRequest($senderID, $recipientID, $groupID)
    {
        $group = User::find($senderID)->groups()->notOneToOne()->find($groupID);
        if (is_null($group) || !is_null($group->members()->find($recipientID))) {
            return false;
        }

        return $group->requests()->firstOrCreate(['sender_id' => $this->id, 'recipient_id' => $recipientID]);
    }

    public function denyRequest($senderID, $recipientID, $groupID)
    {
        return User::find($recipientID)->groupRequestsToMe()->where(['group_id' => $groupID, 'sender_id' => $senderID])->delete();
    }

    public function acceptRequest($recipientID, $groupID)
    {
        $recipient = User::find($recipientID);
        $group = Group::notOneToOne()->find($groupID);
        if (!$recipient->groupRequestsToMe()->where('group_id', $groupID)->exists() || is_null($group)) {
            return false;
        }
        return DB::transaction(function () use ($group, $recipient) {
            $this->join($recipient->id, $group->id);
            $recipient->groupRequestsToMe()->where('group_id', $group->id)->delete();
            return true;
        });
    }

}
