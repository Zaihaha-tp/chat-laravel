<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TypingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RoomController::class, 'home'])->name('home');
Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
Route::post('/rooms/join', [RoomController::class, 'joinByCode'])->name('rooms.join');

Route::middleware('room.active')->group(function () {
    Route::get('/room/{code}', [RoomController::class, 'show'])->name('room.show');
    Route::post('/room/{code}/join', [RoomController::class, 'join'])->name('room.join');
    Route::post('/room/{code}/leave', [RoomController::class, 'leave'])->name('room.leave');

    Route::get('/room/{code}/messages', [MessageController::class, 'index'])->name('room.messages.index');
    Route::post('/room/{code}/messages', [MessageController::class, 'store'])->name('room.messages.store');

    Route::post('/room/{code}/typing', [TypingController::class, 'store'])->name('room.typing.store');
    Route::get('/room/{code}/typing/status', [TypingController::class, 'status'])->name('room.typing.status');
});
