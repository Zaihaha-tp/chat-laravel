<?php

namespace App\Http\Middleware;

use App\Models\Room;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoomIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $room = Room::where('code', $request->route('code'))->first();

        if (! $room) {
            abort(404);
        }

        if ($room->isExpired()) {
            return response()->view('expired', ['room' => $room], 410);
        }

        $request->attributes->set('room', $room);

        return $next($request);
    }
}
