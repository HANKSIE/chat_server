<?php

namespace Database\Seeders;

use App\Models\FriendRequest;
use App\Models\Group;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([ // id: 1
            'name' => "faker",
            'email' => "iamfaker@gmail.com",
            'password' => Hash::make('iamfaker'),
        ]);
        User::factory()->count(15)->create(); // id: 2-16
        User::factory()->create([ // id: 17
            'name' => "hanksie",
            'email' => "hanksie@gmail.com",
            'password' => Hash::make('iamfaker'),
        ]);

        $user = User::find(1);
        $friends = User::whereBetween('id', [15, 16])->get();
        $friends->each(function ($friend) use ($user) {
            $this->makeFriend($user, $friend);
        });
        FriendRequest::create(['sender_id' => 17, 'recipient_id' => 1]);
    }

    public function makeFriend($user1, $user2)
    {
        DB::transaction(function () use ($user1, $user2) {
            $group = Group::create(['is_one_to_one' => true]);
            $group->members()->attach($user1);
            $group->members()->attach($user2);

            $user1->friends()->attach($user2, ['group_id' => $group->id]);
            $user2->friends()->attach($user1, ['group_id' => $group->id]);
            $latestMessageId = -1;
            collect(range(1, 15))->each(function ($data) use ($group, $user2, &$latestMessageId) {
                $message = $group->messages()->create(['body' => $data]);
                $latestMessageId = $message->id;
                $message->user()->associate($user2);
                $message->save();
            });
            MessageRead::create(['user_id' => $user1->id, 'group_id' => $group->id, 'unread' => 15, 'latest_message_id' => $latestMessageId]);
            MessageRead::create(['user_id' => $user2->id, 'group_id' => $group->id, 'unread' => 0, 'message_id' => $latestMessageId, 'latest_message_id' => $latestMessageId]);
        });
    }
}
