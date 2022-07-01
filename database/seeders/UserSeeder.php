<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
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
        User::factory()->count(10)->create();
        User::first()->friendRequests()->create([
            'friend_id' => 2
        ]);
    }
}
