@extends('layouts.app')

@section('title', 'Room ' . $room->code)

@section('content')
<div
    id="chat-root"
    class="overflow-hidden rounded-[10px] bg-[#1D1D1B]"
    data-code="{{ $room->code }}"
    data-user="{{ $user->name }}"
    data-expires-at="{{ $room->expired_at->toIso8601String() }}"
    data-messages-url="{{ route('room.messages.index', $room->code) }}"
    data-send-url="{{ route('room.messages.store', $room->code) }}"
    data-typing-url="{{ route('room.typing.store', $room->code) }}"
    data-typing-status-url="{{ route('room.typing.status', $room->code) }}"
>
    <div class="flex flex-wrap items-center gap-2 border-b border-[#2a2a28] px-4 py-3">
        <span class="text-base font-medium" style="font-family: 'JetBrains Mono', monospace;">{{ $room->code }}</span>
        @if ($room->is_private)
            <span class="rounded-[3px] border border-[#2a2a28] px-1.5 py-0.5 text-[0.7rem] text-[#7B7A74]">private</span>
        @endif
        <span id="online-count" class="text-xs text-[#7B7A74]">· 1 online</span>
        <span id="countdown" class="ml-auto text-xs text-[#7B7A74]" style="font-family: 'JetBrains Mono', monospace;">--:--:--</span>
        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <button id="copy-link" type="button" class="rounded-[3px] border border-[#2a2a28] px-2 py-1 text-xs text-[#7B7A74] hover:text-[#E5E4DF]">Copy link</button>
            <button id="sound-toggle" type="button" aria-pressed="true" class="rounded-[3px] border border-[#2a2a28] px-2 py-1 text-xs text-[#7B7A74] hover:text-[#E5E4DF]">Sound: on</button>
            <form method="POST" action="{{ route('room.leave', $room->code) }}" onsubmit="return confirm('Switch user? You will rejoin this room with a new name.');">
                @csrf
                <input type="hidden" name="return_to" value="room">
                <button type="submit" class="rounded-[3px] border border-[#2a2a28] px-2 py-1 text-xs text-[#7B7A74] hover:text-[#E5E4DF]">Switch user</button>
            </form>
            <form method="POST" action="{{ route('room.leave', $room->code) }}" onsubmit="return confirm('Leave this room?');">
                @csrf
                <button type="submit" class="rounded-[3px] border border-[#2a2a28] px-2 py-1 text-xs text-[#7B7A74] hover:text-red-400">Leave</button>
            </form>
        </div>
    </div>

    <div class="flex items-center gap-2 border-b border-[#2a2a28] px-4 py-2 text-xs text-[#7B7A74]">
        <span>Share:</span>
        <a id="share-wa" href="#" target="_blank" rel="noopener" class="hover:text-[#5FB5D6]">WhatsApp</a>
        <span>·</span>
        <a id="share-tg" href="#" target="_blank" rel="noopener" class="hover:text-[#5FB5D6]">Telegram</a>
        <span class="ml-auto hidden sm:inline">Chatting as <span class="text-[#E5E4DF]">{{ $user->name }}</span></span>
    </div>

    <div id="messages" class="flex h-[55vh] min-h-[320px] flex-col gap-2 overflow-y-auto px-4 py-4"></div>

    <div id="empty-state" class="hidden px-4 pb-2 text-center text-sm text-[#7B7A74]">
        No messages yet — say hi to start the conversation.
    </div>

    <div id="typing" class="hidden h-6 px-4 text-xs text-[#7B7A74]"></div>

    <form id="send-form" class="flex gap-2 border-t border-[#2a2a28] p-3">
        <input
            id="message-input" type="text" maxlength="500" autocomplete="off"
            placeholder="Type a message… (Enter to send)"
            class="flex-1 rounded-md border border-[#2a2a28] bg-[#161614] px-4 py-2.5 text-[0.92rem] text-[#E5E4DF] placeholder-[#7B7A74] outline-none focus:border-[#5FB5D6]"
        >
        <button
            type="submit"
            class="rounded-md bg-[#5FB5D6] px-5 py-2.5 text-sm font-semibold text-[#161614] transition hover:brightness-110"
        >
            Send
        </button>
    </form>
</div>
@endsection
