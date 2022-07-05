<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
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
        User::factory()->count(10)->create();
        User::factory()->create([
            'name' => 'faker',
            'email' => 'iamfaker@gmail.com',
            'password' => Hash::make('iamfaker'),
        ]);
        User::factory()->create([
            'name' => 'faker2',
            'email' => 'iamfaker2@gmail.com',
            'password' => Hash::make('iamfaker2'),
        ]);
        $user1 = User::find(11);
        $user2 = User::find(12);
        $group = Group::create(['is_one_to_one' => true]);
        $group->members()->attach($user1);
        $group->members()->attach($user2);
        $user1->friends()->attach($user2, ['group_id' => $group->id]);
        $user2->friends()->attach($user1, ['group_id' => $group->id]);
    }
}
