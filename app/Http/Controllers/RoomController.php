<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use App\Models\RoomUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mews\Purifier\Facades\Purifier;

class RoomController extends Controller
{
    public function home(): View
    {
        $rooms = Room::active()->public()
            ->withCount(['roomUsers as online_count' => fn ($q) => $q->online()])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('home', ['rooms' => $rooms]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $room = Room::create([
            'code' => Room::generateUniqueCode(),
            'expired_at' => now()->addHours(24),
            'is_private' => (bool) ($validated['is_private'] ?? false),
        ]);

        $user = $this->createUser($room, $validated['name']);
        $request->session()->put($this->sessionKey($room->code), $user->id);

        Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => "{$user->name} joined the room",
            'is_system' => true,
        ]);

        return redirect()->route('room.show', $room->code);
    }

    public function joinByCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:8'],
            'name' => ['required', 'string', 'max:30'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $room = Room::where('code', $code)->first();

        if (! $room || $room->isExpired()) {
            return redirect()->route('home')
                ->withInput()
                ->withErrors(['code' => 'Room not found or already expired.']);
        }

        // Joining by code always means a NEW identity in this room:
        // drop the previous session user (if any) so the joiner never
        // inherits the creator's (or anyone else's) identity.
        $previous = self::currentUser($request, $room);
        if ($previous) {
            $previous->delete();
        }

        $user = $this->createUser($room, $validated['name']);
        $request->session()->put($this->sessionKey($room->code), $user->id);

        Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => "{$user->name} joined the room",
            'is_system' => true,
        ]);

        return redirect()->route('room.show', $room->code);
    }

    public function show(Request $request, string $code): View
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');
        $user = $this->currentUser($request, $room);

        if (! $user) {
            return view('join', ['room' => $room]);
        }

        return view('room', ['room' => $room, 'user' => $user]);
    }

    public function join(Request $request, string $code): RedirectResponse
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');

        if ($this->currentUser($request, $room)) {
            return redirect()->route('room.show', $room->code);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
        ]);

        $user = $this->createUser($room, $validated['name']);
        $request->session()->put($this->sessionKey($room->code), $user->id);

        Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => "{$user->name} joined the room",
            'is_system' => true,
        ]);

        return redirect()->route('room.show', $room->code);
    }

    public function leave(Request $request, string $code): RedirectResponse
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');
        $user = $this->currentUser($request, $room);

        if ($user) {
            Message::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'message' => "{$user->name} left the room",
                'is_system' => true,
            ]);
            $user->delete();
        }

        $request->session()->forget($this->sessionKey($room->code));

        // "Switch user" returns to the room (now showing the join-as-new form);
        // plain Leave goes back home.
        if ($request->input('return_to') === 'room') {
            return redirect()->route('room.show', $room->code)->with('status', 'Enter a new name to rejoin.');
        }

        return redirect()->route('home')->with('status', 'You left the room.');
    }

    public static function sessionKey(string $code): string
    {
        return "tempchat.{$code}";
    }

    public static function currentUser(Request $request, Room $room): ?RoomUser
    {
        $userId = $request->session()->get(self::sessionKey($room->code));

        if (! $userId) {
            return null;
        }

        return RoomUser::where('id', $userId)->where('room_id', $room->id)->first();
    }

    private function createUser(Room $room, string $name): RoomUser
    {
        $clean = trim(Purifier::clean($name, ['HTML.Allowed' => '']));

        return RoomUser::create([
            'room_id' => $room->id,
            'name' => $this->uniqueName($room, $clean === '' ? 'Guest' : $clean),
            'last_seen' => now(),
        ]);
    }

    /** Append " (2)", " (3)", ... when the name is taken in this room. */
    private function uniqueName(Room $room, string $base): string
    {
        $base = mb_substr($base, 0, 30);

        if (! RoomUser::where('room_id', $room->id)->where('name', $base)->exists()) {
            return $base;
        }

        for ($i = 2; $i < 1000; $i++) {
            $candidate = mb_substr($base, 0, 30 - strlen(" ($i)"))." ($i)";
            if (! RoomUser::where('room_id', $room->id)->where('name', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $base.' '.uniqid();
    }
}
