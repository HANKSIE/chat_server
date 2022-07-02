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
        $user1 = User::find(1);
        $user2 = User::find(2);
        $user1->createFriendRequest($user2->id);
        $user2->acceptFriendRequest($user1->id);
    }
}
