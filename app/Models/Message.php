<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Message extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['body', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d h:m',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function toSearchableArray()
    {
        return ['body' => $this->body, 'group_id' => $this->group->id];
    }
}
