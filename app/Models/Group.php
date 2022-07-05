<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_one_to_one',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')->withPivot('is_admin')->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
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
}
