<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypingController extends Controller
{
    /** Write endpoint: called (debounced 300ms) only while the user is typing. */
    public function store(Request $request, string $code): JsonResponse
    {
        $room = $request->attributes->get('room');
        $user = RoomController::currentUser($request, $room);

        if (! $user) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        $user->update(['typing_at' => now(), 'last_seen' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Read-only endpoint for the 2s poller. Never writes typing_at/last_seen,
     * and never includes the requesting user.
     */
    public function status(Request $request, string $code): JsonResponse
    {
        $room = $request->attributes->get('room');
        $user = RoomController::currentUser($request, $room);

        if (! $user) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        $typing = $room->roomUsers()
            ->typing()
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->pluck('name');

        return response()->json(['typing' => $typing->values()]);
    }
}
