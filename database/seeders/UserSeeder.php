<?php

namespace Database\Seeders;

use App\Models\Group;
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
        for ($i = 0; $i <= 5; $i++) {
            $suffix = $i == 0 ? '' : $i;
            User::factory()->create([
                'name' => "faker$suffix",
                'email' => "iamfaker$suffix@gmail.com",
                'password' => Hash::make('iamfaker'),
            ]);
        }

        User::factory()->create([
            'name' => "hanksie",
            'email' => "hanksie@gmail.com",
            'password' => Hash::make('iamfaker'),
        ]);
        User::factory()->create([
            'name' => "hook",
            'email' => "hook@gmail.com",
            'password' => Hash::make('iamfaker'),
        ]);

        function makeFriend($user1, $user2)
        {
            DB::transaction(function () use ($user1, $user2) {
                $group = Group::create(['is_one_to_one' => true]);
                $group->members()->attach($user1);
                $group->members()->attach($user2);
                $user1->friends()->attach($user2, ['group_id' => $group->id]);
                $user2->friends()->attach($user1, ['group_id' => $group->id]);
                collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15])->each(function ($data) use ($group, $user2) {
                    tap($group->messages()->create(['body' => $data]), function ($message) use ($user2) {
                        $message->user()->associate($user2);
                        $message->save();
                    });
                });
            });
        }

        $user = User::find(1);
        $friends = User::whereBetween('id', [2, 6])->get();
        $friends->each(function ($friend) use ($user) {
            makeFriend($user, $friend);
        });
    }
}
