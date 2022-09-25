<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageRead extends Model
{
    use HasFactory;

    protected $table = 'message_read';

    protected $fillable = ['user_id', 'group_id', 'message_id', 'latest_message_id', 'unread'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function latestMessage()
    {
        return $this->belongsTo(Message::class, 'latest_message_id');
    }
}
