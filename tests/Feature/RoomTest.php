<?php

namespace Tests\Feature;

use App\Http\Controllers\RoomController;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_room_redirects_to_chat(): void
    {
        $response = $this->post('/rooms', ['name' => 'Rafif']);

        $room = Room::first();
        $this->assertNotNull($room);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $room->code);
        $this->assertTrue($room->expired_at->isFuture());

        $response->assertRedirect("/room/{$room->code}");
        $this->assertDatabaseHas('room_users', ['room_id' => $room->id, 'name' => 'Rafif']);
        // join system message recorded
        $this->assertDatabaseHas('messages', ['room_id' => $room->id, 'is_system' => true]);
    }

    public function test_duplicate_name_gets_suffix(): void
    {
        $room = Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->addDay()]);

        $this->post("/room/{$room->code}/join", ['name' => 'Rafif']);
        // drop session to simulate a second browser
        $this->flushSession();
        $this->post("/room/{$room->code}/join", ['name' => 'Rafif']);

        $this->assertDatabaseHas('room_users', ['room_id' => $room->id, 'name' => 'Rafif (2)']);
    }

    public function test_send_message_and_rate_limit(): void
    {
        $room = Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->addDay()]);
        $this->post("/room/{$room->code}/join", ['name' => 'Rafif']);

        $this->postJson("/room/{$room->code}/messages", ['message' => 'hello'])
            ->assertCreated();
        // immediate second message is throttled (max 1/sec)
        $this->postJson("/room/{$room->code}/messages", ['message' => 'too fast'])
            ->assertStatus(429);
    }

    public function test_typing_status_is_read_only_and_excludes_self(): void
    {
        $room = Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->addDay()]);
        $this->post("/room/{$room->code}/join", ['name' => 'Rafif']);

        $this->postJson("/room/{$room->code}/typing")->assertOk();

        // self is never listed as typing
        $this->getJson("/room/{$room->code}/typing/status")
            ->assertOk()
            ->assertJson(['typing' => []]);
    }

    public function test_expired_room_returns_gone(): void
    {
        $room = Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->subMinute()]);

        $this->get("/room/{$room->code}")->assertStatus(410);
    }

    public function test_cleanup_command_deletes_expired_rooms(): void
    {
        $room = Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->subMinute()]);

        $this->artisan('rooms:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_join_by_code_creates_new_user(): void
    {
        $room = Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->addDay()]);

        // lowercase code is normalized; joiner picks their own name
        $this->post('/rooms/join', ['code' => 'abcdefgh', 'name' => 'Budi'])
            ->assertRedirect("/room/{$room->code}");

        $this->assertDatabaseHas('room_users', ['room_id' => $room->id, 'name' => 'Budi']);
    }

    public function test_join_by_code_replaces_previous_identity(): void
    {
        // creator makes the room, then "joins" it by code with another name:
        // they must become a new user, never keep the creator identity.
        $this->post('/rooms', ['name' => 'Creator']);
        $room = Room::first();

        $this->post('/rooms/join', ['code' => $room->code, 'name' => 'Guest'])
            ->assertRedirect("/room/{$room->code}");

        $this->assertDatabaseMissing('room_users', ['room_id' => $room->id, 'name' => 'Creator']);
        $this->assertDatabaseHas('room_users', ['room_id' => $room->id, 'name' => 'Guest']);
        $this->assertEquals(1, $room->roomUsers()->count());
    }

    public function test_join_by_code_unknown_or_expired_fails(): void
    {
        $this->post('/rooms/join', ['code' => 'ZZZZZZZZ', 'name' => 'Budi'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('code');

        Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->subMinute()]);
        $this->post('/rooms/join', ['code' => 'ABCDEFGH', 'name' => 'Budi'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('code');
    }

    public function test_two_browsers_get_separate_identities(): void
    {
        // Browser 1: creator makes the room.
        $this->post('/rooms', ['name' => 'Creator']);
        $room = Room::first();
        $creator = $room->roomUsers()->where('name', 'Creator')->firstOrFail();

        // Browser 2: separate session joins via link and chats as Budi.
        $budi = RoomUser::create(['room_id' => $room->id, 'name' => 'Budi', 'last_seen' => now()]);
        $this->withSession([RoomController::sessionKey($room->code) => $budi->id])
            ->postJson("/room/{$room->code}/messages", ['message' => 'hi from Budi'])
            ->assertCreated();

        $this->assertDatabaseHas('messages', ['room_id' => $room->id, 'user_id' => $budi->id, 'message' => 'hi from Budi']);
        $this->assertDatabaseMissing('messages', ['room_id' => $room->id, 'user_id' => $creator->id, 'is_system' => false]);

        // Each viewer sees correct mine flags: Budi's own message is his, creator's is not.
        $res = $this->withSession([RoomController::sessionKey($room->code) => $budi->id])
            ->getJson("/room/{$room->code}/messages?after=0");
        $chat = collect($res->json('messages'))->firstWhere('message', 'hi from Budi');
        $this->assertTrue($chat['mine']);
        $this->assertSame('Budi', $chat['name']);
    }

    public function test_typing_visible_while_typing_cleared_on_send(): void
    {
        $room = Room::create(['code' => 'ABCDEFGH', 'expired_at' => now()->addDay()]);
        $creator = RoomUser::create(['room_id' => $room->id, 'name' => 'Creator', 'last_seen' => now()]);
        $budi = RoomUser::create(['room_id' => $room->id, 'name' => 'Budi', 'last_seen' => now()]);
        $key = RoomController::sessionKey($room->code);

        // Budi is typing → creator's poll sees him immediately.
        $this->withSession([$key => $budi->id])->postJson("/room/{$room->code}/typing")->assertOk();
        $this->withSession([$key => $creator->id])->getJson("/room/{$room->code}/typing/status")
            ->assertOk()
            ->assertJson(['typing' => ['Budi']]);

        // Budi sends the message → his typing flag clears, no ghost indicator.
        $this->withSession([$key => $budi->id])
            ->postJson("/room/{$room->code}/messages", ['message' => 'hello'])
            ->assertCreated();
        $this->withSession([$key => $creator->id])->getJson("/room/{$room->code}/typing/status")
            ->assertOk()
            ->assertJson(['typing' => []]);
    }

    public function test_switch_user_returns_to_join_form(): void
    {
        $this->post('/rooms', ['name' => 'Creator']);
        $room = Room::first();

        $this->post("/room/{$room->code}/leave", ['return_to' => 'room'])
            ->assertRedirect("/room/{$room->code}");

        // session identity cleared → room page now shows the join-as-new form
        $this->get("/room/{$room->code}")
            ->assertOk()
            ->assertSee('Join Room', false);
    }

    public function test_home_lists_only_public_active_rooms(): void
    {
        Room::create(['code' => 'PUBLIC01', 'expired_at' => now()->addDay(), 'is_private' => false]);
        Room::create(['code' => 'PRIVROOM', 'expired_at' => now()->addDay(), 'is_private' => true]);
        Room::create(['code' => 'OLDROOM1', 'expired_at' => now()->subMinute(), 'is_private' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('PUBLIC01');
        $response->assertDontSee('PRIVROOM');
        $response->assertDontSee('OLDROOM1');
    }

    public function test_create_private_room(): void
    {
        $this->post('/rooms', ['name' => 'Rafif', 'is_private' => '1']);

        $this->assertDatabaseHas('rooms', ['is_private' => true]);
    }
}
