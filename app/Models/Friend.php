<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Friend extends Pivot
{
    use HasFactory;

    protected $table = 'friends';

    protected $fillable = [
        'user_id',
        'friend_id',
        'group_id'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
