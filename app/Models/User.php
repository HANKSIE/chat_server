<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function friends()
    {
        return $this->belongsToMany(User::class, Friend::class, 'user_id', 'friend_id')->withPivot('group_id')->withTimestamps();
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')->withPivot('is_admin');
    }

    public function friendRequestsToMe()
    {
        return $this->hasMany(FriendRequest::class, 'recipient_id');
    }

    public function friendRequestsFromMe()
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function groupRequestsToMe()
    {
        return $this->hasMany(GroupRequest::class, 'recipient_id');
    }

    public function groupRequestsFromMe()
    {
        return $this->hasMany(GroupRequest::class, 'sender_id');
    }

    //=======================================================================================

    public function createFriendRequest($id)
    {
        return $this->friendRequestsFromMe()->firstOrCreate(['recipient_id' => $id]);
    }

    public function denyFriendRequest($id)
    {
        return $this->friendRequestsToMe()->where(['sender_id' => $id])->delete();
    }

    public function acceptFriendRequest($user)
    {
        $req = $this->friendRequestsToMe()->where(['sender_id' => $user->id])->first();
        if (is_null($req)) {
            return false;
        }

        return DB::transaction(function () use ($user, $req) {
            $this->beFriend($user);
            $req->delete();
            return true;
        });
    }

    public function beFriend($user)
    {
        return DB::transaction(function () use ($user) {
            $group = Group::create(['is_one_to_one' => true]);
            $this->joinGroup($group, true);
            $user->joinGroup($group, true);
            $this->friends()->attach($user, ['group_id' => $group->id]);
            $user->friends()->attach($this, ['group_id' => $group->id]);
        });
    }

    public function unFriend($user)
    {
        return DB::transaction(function () use ($user) {
            $this->friends()->detach($user->id);
            $user->friends()->detach($this->id);
        });
    }

    public function createGroup($name)
    {
        return DB::transaction(function () use ($name) {
            $group = Group::create(['name' => $name, 'is_one_to_one' => false]);
            $this->joinGroup($group, true);
            return $group;
        });
    }

    public function joinGroup($group, $isAdmin = false)
    {
        return $group->members()->attach($this, ['is_admin' => $isAdmin]);
    }

    public function leaveGroup($group)
    {
        return $group->members()->detach($this);
    }

    public function createGroupRequest($userID, $groupID)
    {
        $group = $this->groups()->notOneToOne()->find($groupID);
        if (is_null($group) || !is_null($group->members()->find($userID))) {
            return false;
        }

        return $group->requests()->firstOrCreate(['sender_id' => $this->id, 'recipient_id' => $userID]);
    }

    public function denyGroupRequest($userID, $groupID)
    {
        return $this->groupRequestsToMe()->where(['group_id' => $groupID, 'sender_id' => $userID])->delete();
    }

    public function acceptGroupRequest($groupID)
    {
        $group = Group::notOneToOne()->find($groupID);
        if (!$this->groupRequestsToMe()->where('group_id', $groupID)->exists() || is_null($group)) {
            return false;
        }

        return DB::transaction(function () use ($group) {
            $this->joinGroup($group);
            $this->groupRequestsToMe()->where('group_id', $group->id)->delete();
            return true;
        });
    }

    public function createMessage($groupID, $body)
    {
        return $this->groups()->find($groupID)->messages()->create(['user_id' => $this->id, 'body' => $body]);
    }
}
