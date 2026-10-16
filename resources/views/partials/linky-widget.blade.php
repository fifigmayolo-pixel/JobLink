@php
    $linkyRole = auth()->user()->role;
    $linkySuggestions = match ($linkyRole) {
        'company' => ['How do I post a job?', 'How do I review applicants?', 'Why is my job posting not visible yet?'],
        'job_coordinator' => ['How do I approve a registration request?', 'How do I send an announcement?', 'How do I change the Tracer Study link?'],
        default => ['How do I complete my profile?', 'How do I apply for a job?', 'I forgot my password'],
    };
@endphp

<style>
    #linkyToggle {
        position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 1040;
        width: 64px; height: 64px; padding: 0; border: 0; border-radius: 50%;
        background: #fff; box-shadow: 0 6px 18px rgba(15,42,92,0.3); cursor: pointer;
        overflow: hidden; transition: transform 0.15s ease;
    }
    #linkyToggle:hover { transform: scale(1.06); }
    #linkyToggle img { width: 100%; height: 100%; object-fit: cover; display: block; }

    #linkyPanel {
        position: fixed; right: 1.25rem; bottom: 5.5rem; z-index: 1040;
        width: min(380px, calc(100vw - 2.5rem)); height: min(560px, calc(100vh - 8rem));
        background: #fff; border-radius: 1rem; box-shadow: 0 12px 36px rgba(15,42,92,0.3);
        display: none; flex-direction: column; overflow: hidden;
        border: 1px solid rgba(15,42,92,0.1);
    }
    #linkyPanel.open { display: flex; }

    .linky-header { background: var(--jl-navy); color: #fff; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.65rem; border-bottom: 3px solid var(--jl-gold); }
    .linky-header img { width: 40px; height: 40px; border-radius: 50%; background: #fff; flex-shrink: 0; }
    .linky-header .linky-title { font-family: 'Fraunces', serif; font-weight: 600; line-height: 1.1; }
    .linky-header .linky-sub { font-size: 0.72rem; opacity: 0.8; }
    .linky-close { margin-left: auto; background: none; border: 0; color: #fff; font-size: 1.2rem; line-height: 1; opacity: 0.85; }
    .linky-close:hover { opacity: 1; }

    #linkyMessages { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 0.6rem; background: var(--jl-cream); }
    .linky-bubble { max-width: 85%; padding: 0.55rem 0.8rem; border-radius: 0.9rem; font-size: 0.9rem; line-height: 1.45; word-wrap: break-word; }
    .linky-bubble.bot { align-self: flex-start; background: #fff; border: 1px solid rgba(15,42,92,0.08); color: var(--jl-ink); border-bottom-left-radius: 0.25rem; }
    .linky-bubble.me { align-self: flex-end; background: var(--jl-navy); color: #fff; border-bottom-right-radius: 0.25rem; }
    .linky-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; }
    .linky-chips button { border: 1px solid rgba(15,42,92,0.25); background: #fff; color: var(--jl-navy); border-radius: 999px; padding: 0.25rem 0.7rem; font-size: 0.8rem; }
    .linky-chips button:hover { background: rgba(201,162,39,0.15); }

    .linky-typing span { display: inline-block; width: 6px; height: 6px; margin-right: 3px; border-radius: 50%; background: var(--jl-slate); animation: linkyBounce 1s infinite ease-in-out; }
    .linky-typing span:nth-child(2) { animation-delay: 0.15s; }
    .linky-typing span:nth-child(3) { animation-delay: 0.3s; }
    @keyframes linkyBounce { 0%, 80%, 100% { opacity: 0.3; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }

    .linky-footer { padding: 0.6rem 0.75rem 0.5rem; border-top: 1px solid rgba(15,42,92,0.08); background: #fff; }
    .linky-footer form { display: flex; gap: 0.5rem; }
    .linky-footer input { flex: 1; }
    .linky-note { font-size: 0.68rem; color: var(--jl-slate); margin: 0.4rem 0 0; text-align: center; }
</style>

<button type="button" id="linkyToggle" aria-label="Open Linky, your JobLink assistant" aria-expanded="false" aria-controls="linkyPanel">
    <img src="{{ asset('images/linky.png') }}" alt="Linky">
</button>

<section id="linkyPanel" role="dialog" aria-label="Chat with Linky">
    <div class="linky-header">
        <img src="{{ asset('images/linky.png') }}" alt="">
        <div>
            <div class="linky-title">Linky</div>
            <div class="linky-sub">Your JobLink PSU ACC Assistant</div>
        </div>
        <button type="button" class="linky-close" id="linkyClose" aria-label="Close chat"><i class="bi bi-x-lg"></i></button>
    </div>

    <div id="linkyMessages" aria-live="polite">
        <div class="linky-bubble bot">Hi! I'm Linky. Ask me how to use JobLink: accounts, profiles, jobs, applications and more.</div>
        <div class="linky-chips" id="linkyChips">
            @foreach($linkySuggestions as $suggestion)
                <button type="button" data-q="{{ $suggestion }}">{{ $suggestion }}</button>
            @endforeach
        </div>
    </div>

    <div class="linky-footer">
        <form id="linkyForm" autocomplete="off">
            <input type="text" id="linkyInput" class="form-control" maxlength="500" placeholder="Type your question…" aria-label="Your question">
            <button type="submit" class="btn btn-jl-gold" id="linkySend" aria-label="Send"><i class="bi bi-send-fill"></i></button>
        </form>
        <p class="linky-note">Linky can make mistakes. Don't share passwords or personal details.</p>
    </div>
</section>

<script>
(function () {
    const STORAGE_KEY = 'linky-chat-{{ auth()->id() }}';
    const MAX_HISTORY = 12;

    const toggle = document.getElementById('linkyToggle');
    const panel = document.getElementById('linkyPanel');
    const closeBtn = document.getElementById('linkyClose');
    const list = document.getElementById('linkyMessages');
    const chips = document.getElementById('linkyChips');
    const form = document.getElementById('linkyForm');
    const input = document.getElementById('linkyInput');
    const sendBtn = document.getElementById('linkySend');

    let history = [];
    let busy = false;

    try { history = JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || []; } catch (e) { history = []; }

    function save() {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history.slice(-MAX_HISTORY))); } catch (e) {}
    }

    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function format(text) {
        return escapeHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/^\s*[-*]\s+/gm, '• ')
            .replace(/\n/g, '<br>');
    }

    function addBubble(who, text, isHtml) {
        const el = document.createElement('div');
        el.className = 'linky-bubble ' + (who === 'me' ? 'me' : 'bot');
        if (isHtml) { el.innerHTML = text; } else { el.innerHTML = who === 'me' ? escapeHtml(text) : format(text); }
        list.appendChild(el);
        list.scrollTop = list.scrollHeight;
        return el;
    }

    function openPanel() {
        panel.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        list.scrollTop = list.scrollHeight;
        setTimeout(function () { input.focus(); }, 50);
    }

    function closePanel() {
        panel.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }

    // Rebuild the earlier conversation when moving between pages in this tab.
    if (history.length) {
        chips.style.display = 'none';
        history.forEach(function (turn) { addBubble(turn.role === 'user' ? 'me' : 'bot', turn.text); });
    }

    async function send(text) {
        text = text.trim();
        if (busy || !text) return;

        busy = true;
        sendBtn.disabled = true;
        input.value = '';
        chips.style.display = 'none';
        addBubble('me', text);

        const typing = addBubble('bot', '<span class="linky-typing"><span></span><span></span><span></span></span>', true);
        const prior = history.slice(-MAX_HISTORY).map(function (t) { return { role: t.role, text: t.text.slice(0, 1500) }; });

        let reply = "Linky isn't available on this page right now.";
        let ok = false;

        try {
            const res = await fetch('{{ route('linky.chat') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ message: text, history: prior }),
            });

            let data = null;
            try { data = await res.json(); } catch (e) {}

            if (data && data.reply) {
                reply = data.reply;
                ok = res.ok;
            } else if (res.status === 419) {
                reply = 'Your session expired. Please refresh the page.';
            } else if (res.status === 422) {
                reply = 'That message is too long. Please keep it short.';
            }
        } catch (e) {
            reply = "I can't reach the server right now. Please try again.";
        }

        typing.remove();
        addBubble('bot', reply);

        if (ok) {
            history.push({ role: 'user', text: text });
            history.push({ role: 'model', text: reply });
            history = history.slice(-MAX_HISTORY);
            save();
        }

        busy = false;
        sendBtn.disabled = false;
        input.focus();
    }

    toggle.addEventListener('click', function () { panel.classList.contains('open') ? closePanel() : openPanel(); });
    closeBtn.addEventListener('click', closePanel);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('open')) closePanel(); });
    form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });
    chips.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-q]');
        if (btn) send(btn.dataset.q);
    });
})();
</script>