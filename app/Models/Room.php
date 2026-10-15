<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['code', 'expired_at', 'is_private'];

    protected $casts = [
        'expired_at' => 'datetime',
        'is_private' => 'boolean',
    ];

    public function roomUsers(): HasMany
    {
        return $this->hasMany(RoomUser::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expired_at', '<=', now());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expired_at', '>', now());
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    public function isExpired(): bool
    {
        return $this->expired_at->lte(now());
    }

    /** Generate a collision-resistant 8-char uppercase alphanumeric code. */
    public static function generateUniqueCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I lookalikes

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function onlineUsers()
    {
        return $this->roomUsers()->online()->get();
    }
}
