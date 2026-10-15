@extends('layouts.app')

@section('title', 'Room expired')

@section('content')
<div class="rounded-[10px] bg-[#1D1D1B] p-6 text-center sm:p-8">
    <h1 class="text-2xl font-medium tracking-tight" style="font-family: 'JetBrains Mono', monospace;">Room expired</h1>
    <p class="mt-2 text-sm text-[#7B7A74]">
        Room <span style="font-family: 'JetBrains Mono', monospace;">{{ $room->code ?? '' }}</span>
        lived its 24 hours — all messages and users were deleted.
    </p>
    <a
        href="{{ route('home') }}"
        class="mt-6 inline-block rounded-md bg-[#5FB5D6] px-5 py-3 text-sm font-semibold text-[#161614] transition hover:brightness-110"
    >
        Create a new room
    </a>
</div>
@endsection
