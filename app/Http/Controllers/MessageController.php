<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Mews\Purifier\Facades\Purifier;

class MessageController extends Controller
{
    public function index(Request $request, string $code): JsonResponse
    {
        $room = $request->attributes->get('room');
        $user = RoomController::currentUser($request, $room);

        if (! $user) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        $user->touchSeen();

        $after = max(0, (int) $request->query('after', 0));

        $messages = Message::with('user:id,name')
            ->forRoom($room->id)
            ->when($after > 0, fn ($q) => $q->afterId($after))
            ->limit(200)
            ->get()
            ->map(fn (Message $m) => $this->present($m, $user->id));

        $online = $room->roomUsers()->online()->orderBy('name')->pluck('name');

        return response()->json([
            'messages' => $messages,
            'online' => $online,
            'online_count' => $online->count(),
            'expires_at' => $room->expired_at->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $room = $request->attributes->get('room');
        $user = RoomController::currentUser($request, $room);

        if (! $user) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        // Simple rate limit: max 1 message per second per user.
        $throttleKey = "tempchat:msg-rate:{$user->id}";
        if (Cache::has($throttleKey)) {
            return response()->json(['message' => 'Slow down — max 1 message per second.'], 429);
        }
        Cache::put($throttleKey, true, 1);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $clean = trim(Purifier::clean($validated['message'], ['HTML.Allowed' => '']));

        if ($clean === '') {
            return response()->json(['message' => 'Message is empty after sanitization.'], 422);
        }

        $message = Message::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'message' => mb_substr($clean, 0, 500),
        ]);

        $user->touchSeen();
        // Message sent = no longer typing: clear immediately so no ghost
        // "typing..." lingers after the message arrives.
        $user->update(['typing_at' => null]);
        $message->load('user:id,name');

        return response()->json(['message' => $this->present($message, $user->id)], 201);
    }

    private function present(Message $message, int $viewerId): array
    {
        return [
            'id' => $message->id,
            'name' => $message->user?->name ?? '?',
            'message' => $message->message,
            'is_system' => $message->is_system,
            'mine' => $message->user_id === $viewerId,
            'time' => $message->created_at?->format('H:i'),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
