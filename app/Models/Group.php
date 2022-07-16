<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Group extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'avatar_url',
        'is_one_to_one',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function requests()
    {
        return $this->hasMany(GroupRequest::class);
    }

    public function scopeNotOneToOne($query)
    {
        $query->where('is_one_to_one', false);
    }

    public function scopeOneToOne($query)
    {
        $query->where('is_one_to_one', true);
    }

    public function toSearchableArray()
    {
        return $this->is_one_to_one ? [] : ['name' => $this->name];
    }
}
