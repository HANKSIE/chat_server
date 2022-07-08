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
        for ($i = 0; $i < 20; $i++) {
            $suffix = $i == 0 ? '' : $i;
            User::factory()->create([
                'name' => "faker$suffix",
                'email' => "iamfaker$suffix@gmail.com",
                'password' => Hash::make('iamfaker'),
            ]);
        }

        function makeFriend($user1, $user2)
        {
            DB::transaction(function () use ($user1, $user2) {
                $group = Group::create(['is_one_to_one' => true]);
                $group->members()->attach($user1);
                $group->members()->attach($user2);
                $user1->friends()->attach($user2, ['group_id' => $group->id]);
                $user2->friends()->attach($user1, ['group_id' => $group->id]);
            });
        }

        $user = User::find(1);
        $friends = User::whereBetween('id', [10, 15])->get();
        $friends->each(function ($friend) use ($user) {
            makeFriend($user, $friend);
        });
    }
}
