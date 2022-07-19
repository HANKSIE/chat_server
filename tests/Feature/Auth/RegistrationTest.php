<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register()
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $user = User::firstWhere('email', 'test@example.com');

        $response->assertOk()->assertJson(function (AssertableJson $json) use ($user) {
            $json->has('user', function (AssertableJson $json) use ($user) {
                $json->whereAll([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->etc();
            });
        });
    }
}
