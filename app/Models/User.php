<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar_url',
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
        return $this->belongsToMany(Group::class, 'group_members');
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

    public function toSearchableArray()
    {
        return ['name' => $this->name];
    }
}
