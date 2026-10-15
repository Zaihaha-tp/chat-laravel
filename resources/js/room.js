import axios from 'axios';

const root = document.getElementById('chat-root');
if (! root) {
    throw new Error('chat root missing');
}

const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const {
    code,
    user: userName,
    expiresAt,
    messagesUrl,
    sendUrl,
    typingUrl,
    typingStatusUrl,
} = root.dataset;

const list = document.getElementById('messages');
const emptyState = document.getElementById('empty-state');
const typingEl = document.getElementById('typing');
const onlineCount = document.getElementById('online-count');
const countdownEl = document.getElementById('countdown');
const form = document.getElementById('send-form');
const input = document.getElementById('message-input');
const toasts = document.getElementById('toasts');

let lastId = 0;
let soundOn = true;

// Distinct name color per participant, derived from the name hash
// so it stays stable across reloads and polls.
const NAME_COLORS = [
    '#5FB5D6', // cyan (accent)
    '#A78BFA', // violet
    '#4ADE80', // green
    '#FBBF24', // amber
    '#F472B6', // pink
    '#FB923C', // orange
    '#2DD4BF', // teal
    '#F87171', // red
];

function colorFor(name) {
    let h = 0;
    for (let i = 0; i < name.length; i++) {
        h = (h * 31 + name.charCodeAt(i)) >>> 0;
    }
    return NAME_COLORS[h % NAME_COLORS.length];
}
let audioCtx = null;
const expiresAtMs = new Date(expiresAt).getTime();

// --- helpers ---------------------------------------------------------------

function toast(message, kind = 'info') {
    const el = document.createElement('div');
    el.className = 'rounded-md border border-[#2a2a28] bg-[#1D1D1B] px-3 py-2 text-xs text-[#E5E4DF]';
    if (kind === 'error') el.style.borderColor = '#7f1d1d';
    if (kind === 'success') el.style.borderColor = '#5FB5D6';
    el.textContent = message;
    toasts.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

function beep() {
    if (! soundOn) return;
    try {
        audioCtx ??= new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.frequency.value = 660;
        gain.gain.value = 0.08;
        osc.start();
        osc.stop(audioCtx.currentTime + 0.15);
    } catch {
        // audio unavailable — stay silent
    }
}

function nearBottom() {
    return list.scrollHeight - list.scrollTop - list.clientHeight < 80;
}

function scrollToBottom() {
    list.scrollTop = list.scrollHeight;
}

// --- rendering (textContent only — never innerHTML with user data) ----------

function renderMessage(m) {
    if (m.is_system) {
        const wrap = document.createElement('div');
        wrap.className = 'text-center text-xs text-[#7B7A74]';
        wrap.dataset.id = m.id;
        wrap.textContent = m.message;
        return wrap;
    }

    const wrap = document.createElement('div');
    wrap.dataset.id = m.id;
    wrap.className = m.mine ? 'flex justify-end' : 'flex justify-start';

    const bubble = document.createElement('div');
    bubble.className = m.mine
        ? 'max-w-[75%] rounded-lg rounded-br-sm bg-violet-500 px-3 py-2 text-sm text-white'
        : 'max-w-[75%] rounded-lg rounded-bl-sm bg-[#2a2a28] px-3 py-2 text-sm text-[#E5E4DF]';

    if (! m.mine) {
        const name = document.createElement('div');
        name.className = 'mb-0.5 text-[0.72rem]';
        name.style.fontFamily = "'JetBrains Mono', monospace";
        name.style.color = colorFor(m.name);
        name.textContent = m.name;
        bubble.appendChild(name);
    } else {
        const name = document.createElement('div');
        name.className = 'mb-0.5 text-right text-[0.72rem] text-violet-200';
        name.style.fontFamily = "'JetBrains Mono', monospace";
        name.textContent = `${m.name} (you)`;
        bubble.appendChild(name);
    }

    const body = document.createElement('div');
    body.textContent = m.message;
    bubble.appendChild(body);

    const time = document.createElement('div');
    time.className = m.mine ? 'mt-0.5 text-right text-[0.7rem] text-violet-200' : 'mt-0.5 text-right text-[0.7rem] text-[#7B7A74]';
    time.textContent = m.time ?? '';
    bubble.appendChild(time);

    wrap.appendChild(bubble);
    return wrap;
}

function appendMessages(messages, notify) {
    if (! messages.length) return;
    const stick = nearBottom();
    for (const m of messages) {
        if (m.id <= lastId) continue;
        list.appendChild(renderMessage(m));
        lastId = Math.max(lastId, m.id);
        if (notify && ! m.mine && ! m.is_system) beep();
    }
    emptyState.classList.add('hidden');
    if (stick) scrollToBottom();
}

// --- polling -----------------------------------------------------------------
// Messages stay on the 2s PRD cadence; typing status polls every 1s so the
// indicator appears ~instantly after the other user's first keystroke.
// The typing endpoint is read-only (no writes), so the extra rate is cheap.

async function pollMessages() {
    try {
        const res = await axios.get(messagesUrl, { params: { after: lastId } });
        appendMessages(res.data.messages ?? [], true);

        const online = res.data.online ?? [];
        const count = res.data.online_count ?? online.length;
        onlineCount.textContent = `· ${count} online`;
    } catch {
        // transient failure — next tick retries
    }
}

async function pollTyping() {
    try {
        const res = await axios.get(typingStatusUrl);
        const typing = res.data.typing ?? [];
        if (typing.length) {
            typingEl.classList.remove('hidden');
            typingEl.textContent = '';
            typing.forEach((n, i) => {
                if (i > 0) typingEl.appendChild(document.createTextNode(', '));
                const s = document.createElement('span');
                s.style.color = colorFor(n);
                s.textContent = n;
                typingEl.appendChild(s);
            });
            const rest = document.createElement('span');
            rest.textContent = ' sedang mengetik';
            const dots = document.createElement('span');
            dots.className = 'typing-dots';
            dots.textContent = '...';
            typingEl.append(rest, dots);
        } else {
            typingEl.classList.add('hidden');
            typingEl.textContent = '';
        }
    } catch {
        // transient failure — next tick retries
    }
}

// --- typing: leading-edge throttle — POST immediately on first keystroke,
// then at most once every 2s while typing continues (backend window is 3s).
// A trailing-only debounce would send nothing during continuous typing,
// so the indicator would appear only after the message arrives.

let lastTypingPost = 0;
input.addEventListener('input', () => {
    if (input.value.trim() === '') return;
    const now = Date.now();
    if (now - lastTypingPost >= 2000) {
        lastTypingPost = now;
        axios.post(typingUrl).catch(() => {});
    }
});

// --- send -------------------------------------------------------------------

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (! text) return;
    try {
        const res = await axios.post(sendUrl, { message: text });
        input.value = '';
        appendMessages([res.data.message], false);
        scrollToBottom();
    } catch (err) {
        toast(err.response?.data?.message ?? 'Failed to send.', 'error');
    }
});

// --- countdown ----------------------------------------------------------------

function tickCountdown() {
    const left = expiresAtMs - Date.now();
    if (left <= 0) {
        countdownEl.textContent = 'expired';
        return;
    }
    const h = Math.floor(left / 3600000);
    const m = Math.floor((left % 3600000) / 60000);
    const s = Math.floor((left % 60000) / 1000);
    countdownEl.textContent = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    const warn = left < 3600000;
    countdownEl.classList.toggle('text-red-400', warn);
    countdownEl.classList.toggle('text-[#7B7A74]', ! warn);
}

// --- header actions ------------------------------------------------------------

document.getElementById('copy-link')?.addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(window.location.href);
        toast('Room link copied.', 'success');
    } catch {
        toast('Copy failed — copy the URL manually.', 'error');
    }
});

document.getElementById('sound-toggle')?.addEventListener('click', (e) => {
    soundOn = ! soundOn;
    const btn = e.currentTarget;
    btn.textContent = soundOn ? 'Sound: on' : 'Sound: off';
    btn.setAttribute('aria-pressed', String(soundOn));
});

{
    const text = encodeURIComponent(`Join my TempChat room ${code}: ${window.location.href}`);
    document.getElementById('share-wa').href = `https://wa.me/?text=${text}`;
    document.getElementById('share-tg').href = `https://t.me/share/url?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(`Join my TempChat room ${code}`)}`;
}

// --- boot ----------------------------------------------------------------------

const style = document.createElement('style');
style.textContent = '@keyframes tblink{0%,100%{opacity:.2}50%{opacity:1}}.typing-dots{display:inline-block;animation:tblink 1s infinite}';
document.head.appendChild(style);

axios.get(messagesUrl, { params: { after: 0 } }).then((res) => {
    appendMessages(res.data.messages ?? [], false);
    const online = res.data.online ?? [];
    onlineCount.textContent = `· ${res.data.online_count ?? online.length} online`;
    if (! lastId) emptyState.classList.remove('hidden');
    scrollToBottom();
}).catch(() => {}).finally(() => {
    setInterval(pollMessages, 2000);
    // stagger so the two pollers don't fire in lockstep
    setTimeout(() => {
        pollTyping();
        setInterval(pollTyping, 1000);
    }, 500);
});

tickCountdown();
setInterval(tickCountdown, 1000);
