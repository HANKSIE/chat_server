<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;

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
        return $this->belongsToMany(User::class, Friend::class, 'user_id', 'friend_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')->withPivot('is_admin');
    }

    //=======================================================================================

    public function friendRequestsToMe(){
        return $this->hasMany(FriendRequest::class, 'recipient_id');
    }

    public function friendRequestsFromMe(){
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function createFriendRequest($id)
    {
        return $this->friendRequestsFromMe()->firstOrCreate(['recipient_id'=> $id]);
    }

    public function denyFriendRequest($id)
    {
        return $this->friendRequestsToMe()->where(['sender_id' => $id])->delete();
    }

    public function acceptFriendRequest($id)
    {
        $req = $this->friendRequestsToMe()->where(['sender_id' => $id])->first();
        if(is_null($req)) return false;
        return DB::transaction(function() use ($id, $req) {
            $group = Group::create(['is_one_to_one' => true]);
            $this->joinGroup($group->id, true);
            User::find($id)->joinGroup($group->id, true);
            Friend::create(['group_id' => $group->id, 'user_id' => $this->id, 'friend_id' => $id]);
            Friend::create(['group_id' => $group->id, 'user_id' => $id, 'friend_id' => $this->id]);
            $req->delete();
            return true;
        });
    }

    public function unFriend($id){
        return DB::transaction(function() use ($id){
            Friend::where(['user_id' => $this->id, 'friend_id' => $id])->delete();
            Friend::where(['user_id' => $id, 'friend_id' => $this->id])->delete();
        });
    }

    public function groupRequestsToMe(){
        return $this->hasMany(GroupRequest::class, 'recipient_id');
    }

    public function groupRequestsFromMe(){
        return $this->hasMany(GroupRequest::class, 'sender_id');
    }

    public function createGroup($name){
        return DB::transaction(function() use ($name){
            $group = Group::create(['name' => $name, 'is_one_to_one' => false]);
            $this->joinGroup($group->id, true);
            return $group;
        });
    }

    public function joinGroup($groupID, $isAdmin = false)
    {
        return Group::find($groupID)->members()->attach($this, ['is_admin' => $isAdmin]);
    }

    public function leaveGroup($groupID)
    {
        return $this->groups()->where(['group_id' => $groupID])->delete();
    }

    public function createGroupRequest($userID, $groupID){
        $group = $this->groups()->find($groupID);
        if(is_null($group) || !is_null($group->members()->find($userID))) return false;
        return $group->requests()->firstOrCreate(['sender_id'=> $this->id, 'recipient_id' => $userID]);
    }

    public function denyGroupRequest($userID, $groupID)
    {
        return $this->groupRequestsToMe()->where(['group_id' => $groupID,'sender_id' => $userID])->delete();
    }

    public function acceptGroupRequest($groupID)
    {
        if(!$this->groupRequestsToMe()->where('group_id', $groupID)->exists() || is_null( Group::find($groupID))) return false;
        return DB::transaction(function() use ($groupID) {
            $this->joinGroup($groupID);
            $this->groupRequestsToMe()->where('group_id', $groupID)->delete();
            return true;
        });
    }
}
