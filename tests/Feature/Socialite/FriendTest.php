<?php

namespace Tests\Feature\Socialite;

use App\Events\Socialite\Friend\BeFriend;
use App\Events\Socialite\Friend\UnFriend;
use App\Models\GroupMember;
use App\Models\User;
use App\Repositories\GroupRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FriendTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function getIntersectionGroup($user1, $user2)
    {
        return $this->app->make(GroupRepository::class)->getIntersectionGroups($user1->id, $user2->id, true)[0];
    }

    public function test_send_friend_request()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1);
        $this->postJson(route('friend.request.send'), ['recipient_id' => $user2->id])->assertOk()->assertJson(function (AssertableJson $json) {
            $json->where('be_friend', false);
        });
        $this->assertDatabaseHas('friend_requests', ['sender_id' => $user1->id, 'recipient_id' => $user2->id]);
    }

    public function test_send_friend_request_had_receive_request()
    {
        Event::fake();
        $user1 = User::find(1);
        $user2 = User::find(17);
        $reqData = ['sender_id' => $user2->id, 'recipient_id' => $user1->id];
        $this->assertDatabaseHas('friend_requests', $reqData);
        Sanctum::actingAs($user1);
        $this->postJson(route('friend.request.send'), ['recipient_id' => $user2->id])
            ->assertOk()->assertJson(function (AssertableJson $json) {
            $json->where('be_friend', true)->has('group_id');
        });
        $this->assertDatabaseMissing('friend_requests', $reqData);
        $this->assertDatabaseHas('friends', ['user_id' => $user1->id, 'friend_id' => $user2->id]);
        $this->assertDatabaseHas('friends', ['user_id' => $user2->id, 'friend_id' => $user1->id]);
        Event::assertDispatched(BeFriend::class);
    }

    public function test_accept_friend_request()
    {
        Event::fake();
        $user1 = User::find(1);
        $user2 = User::find(17);
        $reqData = ['sender_id' => $user2->id, 'recipient_id' => $user1->id];
        $this->assertDatabaseHas('friend_requests', $reqData);
        Sanctum::actingAs($user1);
        $res = $this->postJson(route('friend.request.accept'), ['sender_id' => $user2->id])
            ->assertOk()->assertJson(function (AssertableJson $json) {
            $json->has('group_id');
        });
        $groupID = $res->getOriginalContent()['group_id'];
        $this->assertDatabaseMissing('friend_requests', $reqData);
        $this->assertDatabaseHas('friends', ['user_id' => $user1->id, 'friend_id' => $user2->id]);
        $this->assertDatabaseHas('friends', ['user_id' => $user2->id, 'friend_id' => $user1->id]);
        $this->assertDatabaseHas('message_read', ['user_id' => $user1->id, 'group_id' => $groupID, 'message_id' => null]);
        $this->assertDatabaseHas('message_read', ['user_id' => $user2->id, 'group_id' => $groupID, 'message_id' => null]);
        Event::assertDispatched(BeFriend::class);
    }

    public function test_accept_friend_request_fail()
    {
        Event::fake();
        $user1 = User::find(1);
        $user2 = User::find(2);
        $reqData = ['sender_id' => $user2->id, 'recipient_id' => $user1->id];
        $this->assertDatabaseMissing('friend_requests', $reqData);
        Sanctum::actingAs($user1);
        $this->postJson(route('friend.request.accept'), ['sender_id' => $user2->id])
            ->assertNotFound();
        Event::assertNotDispatched(BeFriend::class);
    }

    public function test_deny_friend_request()
    {
        $user1 = User::find(1);
        $user2 = User::find(17);
        $reqData = ['sender_id' => $user2->id, 'recipient_id' => $user1->id];
        $this->assertDatabaseHas('friend_requests', $reqData);
        Sanctum::actingAs($user1);
        $this->postJson(route('friend.request.deny'), ['sender_id' => $user2->id])
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('friend_requests', $reqData);
    }

    public function test_revoke_friend_request()
    {
        $user1 = User::find(17);
        $user2 = User::find(1);

        $reqData = ['sender_id' => $user1->id, 'recipient_id' => $user2->id];
        $this->assertDatabaseHas('friend_requests', $reqData);
        Sanctum::actingAs($user1);
        $this->postJson(route('friend.request.revoke'), ['recipient_id' => $user2->id])
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('friend_requests', $reqData);
    }

    public function test_unfriend()
    {
        Event::fake();
        $user1 = User::find(1);
        $user2 = User::find(15);
        Sanctum::actingAs($user1);
        $group = $this->getIntersectionGroup($user1, $user2); // get group before unfriend.
        $this->postJson(route('friend.unfriend'), ['friend_id' => $user2->id])->assertNoContent();
        $this->assertNotNull(GroupMember::withTrashed()->where(['user_id' => $user1->id, 'group_id' => $group->id])->first());
        $this->assertNotNull(GroupMember::withTrashed()->where(['user_id' => $user2->id, 'group_id' => $group->id])->first());
        $this->assertDatabaseMissing('friends', ['user_id' => $user1->id, 'friend_id' => $user2->id]);
        $this->assertDatabaseMissing('friends', ['user_id' => $user2->id, 'friend_id' => $user1->id]);
        Event::assertDispatched(UnFriend::class);
    }

    public function test_friend_paginate()
    {
        $user1 = User::find(1);
        Sanctum::actingAs($user1);
        $res = $this->getJson(
            route('friend.paginate',
                [
                    'per_page' => 5,
                ]
            ));
        $res
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json
                    ->has('data.0', function ($json) {
                        $json->whereAll(['user.id' => 15, 'group_id' => 1]);
                    })
                    ->has('data.1', function ($json) {
                        $json->whereAll(['user.id' => 16, 'group_id' => 2]);
                    })
                    ->count('data', 2)
                    ->etc();
            });
    }

    public function test_find_new_friend_paginate()
    {
        $user1 = User::find(1);
        Sanctum::actingAs($user1);
        $res = $this->getJson(
            route('friend.find-new-friend-paginate',
                [
                    'per_page' => 5,
                ]
            ));
        $res
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                collect()->range(0, 4)->each(function ($i) use ($json) {
                    $json
                        ->has("data.$i", function ($json) use ($i) {
                            $json->where('user.id', $i + 1)->where('state', $i === 0 ? '1' : '0');
                        });
                });

                $json->count('data', 5)->etc();
            });
    }

    public function test_friend_request_paginate()
    {
        $user1 = User::find(1);
        Sanctum::actingAs($user1);
        $res = $this->getJson(
            route('friend.request.paginate',
                [
                    'type' => 'receive',
                    'per_page' => 5,
                ]
            ));

        $res
            ->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json
                    ->has('data.0', function ($json) {
                        $json->where('id', 17)->etc();
                    })
                    ->etc()
                    ->count('data', 1);
            });
    }

}
