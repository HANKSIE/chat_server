<?php

namespace Tests\Feature\Auth;

use App\Events\GroupMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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
        $user2 = User::find(2);
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
        $this->assertDatabaseHas('messages', ['group_id' => $group->id, 'body' => $body, 'user_id' => $user1->id]);
    }
}
