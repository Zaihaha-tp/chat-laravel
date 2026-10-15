@extends('layouts.app')

@section('title', 'Join room ' . $room->code)

@section('content')
<div class="rounded-[10px] bg-[#1D1D1B] p-6 sm:p-8">
    <p class="text-xs uppercase tracking-widest text-[#7B7A74]" style="font-family: 'JetBrains Mono', monospace;">You are joining</p>
    <h1 class="mt-1 text-3xl font-medium tracking-tight" style="font-family: 'JetBrains Mono', monospace;">{{ $room->code }}</h1>
    <p class="mt-2 text-sm text-[#7B7A74]">
        Expires {{ $room->expired_at->diffForHumans() }}. Pick a display name to enter.
    </p>

    <form method="POST" action="{{ route('room.join', $room->code) }}" class="mt-6">
        @csrf
        <label for="name" class="mb-2 block text-xs uppercase tracking-widest text-[#7B7A74]" style="font-family: 'JetBrains Mono', monospace;">
            Your name
        </label>
        <input
            id="name" name="name" type="text" required maxlength="30" autocomplete="off"
            value="{{ old('name') }}" placeholder="e.g. Rafif" autofocus
            class="w-full rounded-md border border-[#2a2a28] bg-[#161614] px-4 py-3 text-[0.92rem] text-[#E5E4DF] placeholder-[#7B7A74] outline-none focus:border-[#5FB5D6]"
        >
        @error('name')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror

        <button
            type="submit"
            class="mt-4 w-full rounded-md bg-[#5FB5D6] px-5 py-3 text-sm font-semibold text-[#161614] transition hover:brightness-110 sm:w-auto"
        >
            Join Room
        </button>
    </form>
</div>
@endsection
