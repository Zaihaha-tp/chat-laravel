@extends('layouts.app')

@section('title', 'Create a room')

@section('content')
<div class="rounded-[10px] bg-[#1D1D1B] p-6 sm:p-8">
    <h1 class="text-2xl font-medium tracking-tight sm:text-3xl" style="font-family: 'JetBrains Mono', monospace;">
        Temporary chat rooms
    </h1>
    <p class="mt-2 text-sm leading-relaxed text-[#7B7A74]">
        Create a room, share the link, chat. Everything auto-deletes after 24 hours.
        No account, no history, no traces.
    </p>

    <form method="POST" action="{{ route('rooms.store') }}" class="mt-6">
        @csrf
        <label for="name" class="mb-2 block text-xs uppercase tracking-widest text-[#7B7A74]" style="font-family: 'JetBrains Mono', monospace;">
            Your name
        </label>
        <input
            id="name" name="name" type="text" required maxlength="30" autocomplete="off"
            value="{{ old('name') }}" placeholder="e.g. Rafif"
            class="w-full rounded-md border border-[#2a2a28] bg-[#161614] px-4 py-3 text-[0.92rem] text-[#E5E4DF] placeholder-[#7B7A74] outline-none focus:border-[#5FB5D6]"
        >
        @error('name')
            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
        @enderror

        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-[#7B7A74]">
            <input type="checkbox" name="is_private" value="1" class="h-4 w-4 accent-[#5FB5D6]">
            Private room — hidden from the public list, joinable only via link
        </label>

        <button
            type="submit"
            class="mt-4 w-full rounded-md bg-[#5FB5D6] px-5 py-3 text-sm font-semibold text-[#161614] transition hover:brightness-110 sm:w-auto"
        >
            Create Room
        </button>
    </form>
</div>

<div class="mt-6 rounded-[10px] bg-[#1D1D1B] p-6 sm:p-8">
    <h2 class="text-lg font-medium tracking-tight" style="font-family: 'JetBrains Mono', monospace;">Join a room</h2>
    <p class="mt-1 text-sm text-[#7B7A74]">Enter the code and pick a name — you always join as a new user.</p>

    <form method="POST" action="{{ route('rooms.join') }}" class="mt-4 flex flex-col gap-2">
        <div class="flex flex-col gap-2 sm:flex-row">
            <input
                name="code" type="text" required maxlength="8" autocomplete="off"
                value="{{ old('code') }}" placeholder="X7K9P2MQ"
                style="font-family: 'JetBrains Mono', monospace;"
                class="flex-1 rounded-md border border-[#2a2a28] bg-[#161614] px-4 py-3 text-[0.92rem] uppercase tracking-widest text-[#E5E4DF] placeholder-[#7B7A74] outline-none focus:border-[#5FB5D6]"
            >
            <input
                name="name" type="text" required maxlength="30" autocomplete="off"
                value="{{ old('name') }}" placeholder="Your name"
                class="flex-1 rounded-md border border-[#2a2a28] bg-[#161614] px-4 py-3 text-[0.92rem] text-[#E5E4DF] placeholder-[#7B7A74] outline-none focus:border-[#5FB5D6]"
            >
            <button
                type="submit"
                class="rounded-md border border-[#5FB5D6] px-5 py-3 text-sm font-semibold text-[#5FB5D6] transition hover:bg-[#5FB5D6] hover:text-[#161614]"
            >
                Join
            </button>
        </div>
    </form>
    @error('code')
        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
    @enderror
    @error('name')
        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>

<div class="mt-6 rounded-[10px] bg-[#1D1D1B] p-6 sm:p-8">
    <h2 class="text-lg font-medium tracking-tight" style="font-family: 'JetBrains Mono', monospace;">Public rooms</h2>
    @if ($rooms->isEmpty())
        <p class="mt-2 text-sm text-[#7B7A74]">No public rooms right now — create one above.</p>
    @else
        <ul class="mt-4 divide-y divide-[#2a2a28]">
            @foreach ($rooms as $r)
                <li>
                    <a href="{{ route('room.show', $r->code) }}" class="flex items-center gap-3 py-2.5 transition hover:bg-[#222220]">
                        <span class="text-sm font-medium tracking-widest" style="font-family: 'JetBrains Mono', monospace;">{{ $r->code }}</span>
                        <span class="text-xs text-[#7B7A74]">· {{ $r->online_count }} online</span>
                        <span class="ml-auto text-xs text-[#7B7A74]">expires {{ $r->expired_at->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
