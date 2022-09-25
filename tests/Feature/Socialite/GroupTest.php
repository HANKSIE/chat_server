<?php

namespace Tests\Feature\Socialite;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_get_recent_friend_group()
    {
        $user1 = User::find(1);
        Sanctum::actingAs($user1);
        $res = $this->getJson(
            route('group.recent-contact.paginate',
                [
                    'is_one_to_one' => true,
                    'per_page' => 5,
                ]
            ));
        $res
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json
                    ->has('data.0', function ($json) {
                        $json->where('message.id', 30)->where('unread', '15')->has('message.group.members');
                    })
                    ->has('data.1', function ($json) {
                        $json->where('message.id', 15)->where('unread', '15')->has('message.group.members');
                    })
                    ->count('data', 2)
                    ->etc();
            });
    }

}
