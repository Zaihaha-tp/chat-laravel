<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('name', 30);
            $table->timestamp('last_seen')->nullable()->index();
            $table->timestamp('typing_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_users');
    }
};
