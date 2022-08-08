<?php

namespace Tests\Feature\Socialite;

use App\Events\GroupMessage;
use App\Models\Group;
use App\Models\User;
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

    public function test_send_message()
    {
        Event::fake();
        $user1 = User::find(1);
        $user2 = User::find(15);
        $group = $user1->groups()->oneToOne()->whereHas('members', function ($query) use ($user2) {
            $query->where('user_id', $user2->id);
        })->first();
        $body = $this->faker->text();
        Sanctum::actingAs($user1);
        $res = $this->postJson('messages', ['group_id' => $group->id, 'body' => $body])
            ->assertOk()
            ->assertJson(function (AssertableJson $json) use ($group, $user1, $body) {
                $json->whereAll([
                    'message.body' => $body,
                    'message.group.id' => $group->id,
                    'message.user.id' => $user1->id,
                ])->has('message.group.members', 2)
                    ->etc();
            });
        $messageID = $res->getOriginalContent()['message']['id'];

        Event::assertDispatched(function (GroupMessage $event) use ($messageID) {
            return $event->message->id === $messageID;
        });
        $this->assertDatabaseHas('messages', ['id' => $messageID, 'group_id' => $group->id, 'body' => $body, 'user_id' => $user1->id]);
        $this->assertDatabaseHas('message_read', ['user_id' => $user1->id, 'group_id' => $group->id, 'message_id' => $messageID]);
    }

    public function test_message_mark_as_read()
    {
        $user = User::find(1);
        $group = Group::find(1);
        Sanctum::actingAs($user);
        $this->assertDatabaseHas('message_read', ['user_id' => $user->id, 'group_id' => $group->id, 'message_id' => null]);
        $this->putJson(route('message.mark-as-read', ['group_id' => 1]))->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas('message_read', ['user_id' => $user->id, 'group_id' => $group->id, 'message_id' => $group->latestMessage->id]);
    }
}