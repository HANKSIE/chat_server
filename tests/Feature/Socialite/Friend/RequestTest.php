<?php

namespace Tests\Feature\Socialite\Friend;

use App\Events\BeFriend;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_friend_request()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1);
        $this->postJson(route('friend.request.send'), ['recipient_id' => $user2->id])
            ->assertOk()->assertJson(function (AssertableJson $json) {
            $json->where('be_friend', false);
        });

        $this->assertDatabaseHas('friend_requests', ['sender_id' => $user1->id, 'recipient_id' => $user2->id]);
    }

    public function test_send_friend_request_had_receive_request()
    {
        Event::fake();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reqData = ['sender_id' => $user2->id, 'recipient_id' => $user1->id];
        FriendRequest::create($reqData);
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
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reqData = ['sender_id' => $user2->id, 'recipient_id' => $user1->id];
        FriendRequest::create($reqData);
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

    public function test_deny_friend_request()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reqData = ['sender_id' => $user2->id, 'recipient_id' => $user1->id];
        FriendRequest::create($reqData);
        Sanctum::actingAs($user1);
        $this->postJson(route('friend.request.deny'), ['sender_id' => $user2->id])
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('friend_requests', $reqData);
    }

    public function test_revoke_friend_request()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $reqData = ['sender_id' => $user1->id, 'recipient_id' => $user2->id];
        FriendRequest::create($reqData);
        Sanctum::actingAs($user1);
        $this->deleteJson(route('friend.request.revoke'), ['recipient_id' => $user2->id])
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseMissing('friend_requests', $reqData);
    }
}
