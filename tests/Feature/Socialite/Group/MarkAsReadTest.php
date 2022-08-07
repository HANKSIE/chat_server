<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarkAsReadTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_send_friend_request()
    {
        $user = User::find(1);
        Sanctum::actingAs($user);
        $this->assertDatabaseHas('message_read', ['user_id' => 1, 'group_id' => 1, 'count' => 0]);
        $this->putJson(route('message.mark-as-read', ['group_id' => 1]))->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas('message_read', ['user_id' => 1, 'group_id' => 1, 'count' => 15]);
    }

}
