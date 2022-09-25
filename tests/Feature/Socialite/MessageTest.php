<?php

namespace Tests\Feature\Socialite;

use App\Events\Socialite\Message\MarkAsRead;
use App\Events\Socialite\Message\SendMessage;
use App\Models\Group;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use App\Repositories\GroupRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpFaker();
        $this->seed();
    }

    private function getIntersectionGroup($user1, $user2)
    {
        return $this->app->make(GroupRepository::class)->getIntersectionGroups($user1->id, $user2->id, true)[0];
    }

    public function test_send_message()
    {
        Event::fake();
        $user1 = User::find(1);
        $user2 = User::find(15);

        $group = $this->getIntersectionGroup($user1, $user2);
        $user2MessageRead = MessageRead::where(['user_id' => $user2->id, 'group_id' => $group->id])->first();
        Sanctum::actingAs($user1);
        $body = $this->faker->text();
        $res = $this->postJson("group/{$group->id}/messages", ['body' => $body]);
        $message = Message::find($res->getOriginalContent()['message']['id']);
        $res->assertOk()
            ->assertJson(function (AssertableJson $json) use ($group, $user1, $message) {
                $json->whereAll([
                    'message.body' => $message->body,
                    'message.group.id' => $group->id,
                    'message.user.id' => $user1->id,
                ])->has('message.group.members', 2)
                    ->etc();
            });
        Event::assertDispatched(function (SendMessage $event) use ($message) {
            return $event->message->id === $message->id;
        });
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'group_id' => $group->id, 'user_id' => $user1->id]);
        $this->assertDatabaseHas('message_read', ['user_id' => $user1->id, 'group_id' => $group->id, 'message_id' => $message->id]);
        $this->assertDatabaseHas('message_read', ['user_id' => $user2->id, 'group_id' => $group->id, 'message_id' => $user2MessageRead->message_id]);
        $this->assertDatabaseHas('groups', ['id' => $group->id, 'latest_message_id' => $message->id]);
    }

    public function test_send_message_forbidden()
    {
        $user1 = User::find(1);
        $user2 = User::find(15);
        Sanctum::actingAs($user1);
        $group = $this->getIntersectionGroup($user1, $user2); // get group before unfriend.

        Event::fakeFor(function () {
            $this->postJson(route('friend.unfriend'), ['friend_id' => 15]);
            Event::assertDispatched(SendMessage::class);
        });

        Event::fakeFor(function () use ($group) {
            $this->postJson("group/{$group->id}/messages", ['body' => 'any'])->assertForbidden();
            Event::assertNotDispatched(SendMessage::class);
        });

    }

    public function test_message_mark_as_read()
    {
        Event::fake();
        $user = User::find(1);
        $group = Group::find(1);
        Sanctum::actingAs($user);
        $this->assertDatabaseHas('message_read', ['user_id' => $user->id, 'group_id' => $group->id, 'message_id' => null, 'unread' => 15]);
        $this->postJson(route('message.mark-as-read', [1]))->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas('message_read', ['user_id' => $user->id, 'group_id' => $group->id, 'message_id' => $group->latestMessage->id, 'unread' => 0]);
        Event::assertDispatched(MarkAsRead::class);
    }
}
