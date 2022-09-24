<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'avatar_url',
        'is_one_to_one',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, GroupMember::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function messageReads()
    {
        return $this->hasMany(MessageRead::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeNotOneToOne($query)
    {
        $query->where('is_one_to_one', false);
    }

    public function scopeOneToOne($query)
    {
        $query->where('is_one_to_one', true);
    }
}
