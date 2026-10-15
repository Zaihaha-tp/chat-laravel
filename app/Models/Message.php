<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['room_id', 'user_id', 'message', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(RoomUser::class, 'user_id');
    }

    public function scopeForRoom(Builder $query, int $roomId): Builder
    {
        return $query->where('room_id', $roomId)->orderBy('id');
    }

    public function scopeAfterId(Builder $query, int $lastId): Builder
    {
        return $query->where('id', '>', $lastId)->orderBy('id');
    }
}
