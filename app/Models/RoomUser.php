<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomUser extends Model
{
    protected $fillable = ['room_id', 'name', 'last_seen', 'typing_at'];

    protected $casts = [
        'last_seen' => 'datetime',
        'typing_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    /** Online = seen within the last 30 seconds. */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('last_seen', '>=', now()->subSeconds(30));
    }

    /** Typing = typing_at within the last 3 seconds. */
    public function scopeTyping(Builder $query): Builder
    {
        return $query->where('typing_at', '>=', now()->subSeconds(3));
    }

    public function isOnline(): bool
    {
        return $this->last_seen && $this->last_seen->gte(now()->subSeconds(30));
    }

    public function isTyping(): bool
    {
        return $this->typing_at && $this->typing_at->gte(now()->subSeconds(3));
    }

    public function touchSeen(): void
    {
        $this->update(['last_seen' => now()]);
    }
}
