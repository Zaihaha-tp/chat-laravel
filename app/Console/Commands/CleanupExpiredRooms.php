<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;

class CleanupExpiredRooms extends Command
{
    protected $signature = 'rooms:cleanup';

    protected $description = 'Delete expired rooms (messages and users go via cascade delete)';

    public function handle(): int
    {
        $count = Room::expired()->count();
        Room::expired()->delete();

        $this->info("Deleted {$count} expired room(s).");

        return self::SUCCESS;
    }
}
