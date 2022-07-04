<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
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
        $user1->createFriendRequest($user2->id);
        $user2->acceptFriendRequest($user1);
    }
}
