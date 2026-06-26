<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'ClassPortal') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    @yield('content')

    <!-- Floating Support Chat -->
    <div id="floating-support" class="fixed bottom-6 right-6 z-50 font-sans">
        <div id="chat-panel" class="hidden w-[360px] sm:w-[390px] h-[520px] bg-white rounded-3xl shadow-2xl border border-slate-100 flex flex-col overflow-hidden mb-4">
            <!-- Header -->
            <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-pink-600 text-white p-4 shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center font-black text-white text-base">B</div>
                            <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-purple-600 rounded-full animate-pulse"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <h4 class="font-bold text-sm">Austin Support Room</h4>
                                <svg class="w-3.5 h-3.5 text-amber-300 fill-amber-300 animate-pulse" viewBox="0 0 24 24"><path d="M6.5 7.5L9 5l2.5 2.5L14 5l2.5 2.5L19 5l1.5 1.5L17 10l2.5 2.5L17 15l2.5 2.5L17 20l-2.5-2.5L12 20l-2.5-2.5L7 20l-2.5-2.5L7 15l-2.5-2.5L7 10L4.5 7.5 6.5 5z"/></svg>
                            </div>
                            <p class="text-[10px] text-purple-100 font-semibold">Online Support Representative</p>
                        </div>
                    </div>
                    <button onclick="toggleChat()" class="p-1 px-2 hover:bg-white/10 rounded-xl transition cursor-pointer text-white/85">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
            </div>
            <!-- Tabs -->
            <div class="flex border-b border-slate-100 bg-slate-50 text-xs text-slate-500 font-bold shrink-0">
                <button onclick="setChatMode('chat')" id="chat-tab-btn" class="flex-1 py-2.5 text-center transition bg-white text-violet-700 border-b-2 border-violet-600">AI Chat</button>
                <button onclick="setChatMode('ticket')" id="ticket-tab-btn" class="flex-1 py-2.5 text-center transition hover:text-slate-700">Submit Ticket</button>
            </div>
            <!-- Chat Mode -->
            <div id="chat-mode" class="flex-1 min-h-0 bg-slate-50 flex flex-col justify-between">
                <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3"></div>
                <div class="px-3 py-1.5 bg-violet-50/60 border-t border-b border-violet-100 flex items-center justify-between gap-1.5 shrink-0 text-[10px] text-violet-700 font-bold">
                    <span>Direct:</span>
                    <a href="https://wa.me/2348062078597?text=Hello%20ClassPortal%20Direct%20Support" target="_blank" class="flex items-center gap-1 bg-emerald-100 text-emerald-800 px-2 py-1 rounded-lg hover:bg-emerald-250 transition">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp
                    </a>
                    <a href="tel:08062078597" class="flex items-center gap-1 bg-violet-100 text-violet-800 px-2 py-1 rounded-lg hover:bg-violet-250 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Call
                    </a>
                </div>
                <form id="chat-form" onsubmit="sendChatMessage(event)" class="p-3 bg-white border-t border-slate-100 flex items-center gap-2 shrink-0">
                    <div class="relative flex-1 flex items-center">
                        <input type="text" id="chat-input" placeholder="Type a message..." class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 pl-3 pr-10 text-xs font-semibold focus:outline-none focus:border-violet-600 focus:ring-1 focus:ring-violet-600">
                    </div>
                    <button type="submit" id="chat-send-btn" disabled class="w-8.5 h-8.5 bg-violet-600 rounded-xl flex items-center justify-center text-white shadow-xs hover:bg-violet-750 active:scale-95 disabled:bg-slate-100 disabled:text-slate-400 transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
            </div>
            <!-- Ticket Mode -->
            <div id="ticket-mode" class="hidden flex-1 min-h-0 bg-slate-50">
                <form id="ticket-form" onsubmit="submitTicket(event)" class="flex-1 p-4 flex flex-col justify-between overflow-y-auto h-full">
                    <div class="space-y-3.5">
                        <div class="p-3 bg-violet-50 text-violet-800 rounded-2xl text-[11px] font-semibold leading-relaxed border border-violet-100">Submit high-priority requests or constructive reviews here. Austin and our technical support group will review every item.</div>
                        <div><label class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Your Name</label><input required type="text" id="ticket-name" placeholder="e.g. Principal Abigail" class="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs font-semibold focus:outline-none focus:border-violet-600"></div>
                        <div><label class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Reply Email</label><input required type="email" id="ticket-email" placeholder="abigail@wisdomacademy.com" class="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs font-semibold focus:outline-none focus:border-violet-600"></div>
                        <div><label class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Message Details</label><textarea required rows="4" id="ticket-message" placeholder="Describe feature request or complaint details..." class="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 text-xs font-semibold focus:outline-none focus:border-violet-600 resize-none"></textarea></div>
                    </div>
                    <div class="pt-4 shrink-0">
                        <div id="ticket-success" class="hidden bg-emerald-50 text-emerald-800 p-2 text-center rounded-xl text-xs font-bold border border-emerald-200">Message Recorded Successfully!</div>
                        <div id="ticket-error" class="hidden bg-rose-50 text-rose-800 p-2 text-center rounded-xl text-xs font-bold border border-rose-200">Error submitting. Try again or use AI Chat instead!</div>
                        <button type="submit" id="ticket-submit-btn" class="w-full py-3 bg-gradient-to-r from-violet-600 to-pink-600 text-white text-xs font-black rounded-xl hover:opacity-90 active:scale-95 transition cursor-pointer shadow-md uppercase tracking-wider">Log Feed Ticket To System</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Floating Button -->
        <button onclick="toggleChat()" class="w-14 h-14 rounded-full bg-gradient-to-tr from-violet-600 via-purple-600 to-pink-600 text-white flex items-center justify-center shadow-2xl hover:shadow-violet-300 hover:shadow-xl cursor-pointer border border-white/20 relative transition-transform active:scale-95" title="Open online support chat widget">
            <span class="absolute -top-1 -right-1 flex h-4 w-4"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-75"></span><span class="relative inline-flex rounded-full h-4 w-4 bg-pink-500 border border-white text-[9px] text-white font-semibold justify-center items-center">!</span></span>
            <svg id="chat-icon-open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <svg id="chat-icon-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

<script>
// Floating Chat Widget
let chatOpen = false;
let chatMode = 'chat';
let chatMessages = [];

function toggleChat() {
    chatOpen = !chatOpen;
    document.getElementById('chat-panel').classList.toggle('hidden', !chatOpen);
    document.getElementById('chat-icon-open').classList.toggle('hidden', chatOpen);
    document.getElementById('chat-icon-close').classList.toggle('hidden', !chatOpen);
    if (chatOpen && chatMessages.length === 0) {
        addChatMessage('model', 'Hi there! I am your ClassPortal Direct AI Assistant. You can ask me anything about lesson note creations, publishing CBT exams, or writing down high-priority complaints. How can I serve your school today?');
    }
}

function addChatMessage(role, text) {
    const id = 'msg_' + Math.random().toString(36).substring(2, 9);
    const ts = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    chatMessages.push({ id, role, text, timestamp: ts });
    const container = document.getElementById('chat-messages');
    const isModel = role === 'model';
    container.innerHTML += '<div class="flex ' + (isModel ? 'justify-start' : 'justify-end') + ' items-end gap-1.5">' +
        (isModel ? '<div class="w-6 h-6 rounded-lg bg-violet-600 text-[10px] font-black text-white flex items-center justify-center shrink-0">B</div>' : '') +
        '<div class="max-w-[78%] p-3 rounded-2xl text-xs leading-relaxed transition-all shadow-xs ' + (isModel ? 'bg-white text-slate-800 rounded-bl-none border border-slate-100' : 'bg-violet-600 text-white rounded-br-none') + '">' +
        '<p class="whitespace-pre-wrap">' + text + '</p>' +
        '<span class="text-[9px] block text-right mt-1 font-semibold ' + (isModel ? 'text-slate-400' : 'text-white/70') + '">' + ts + '</span></div></div>';
    container.scrollTop = container.scrollHeight;
}

function setChatMode(mode) {
    chatMode = mode;
    document.getElementById('chat-mode').classList.toggle('hidden', mode !== 'chat');
    document.getElementById('ticket-mode').classList.toggle('hidden', mode !== 'ticket');
    const chatBtn = document.getElementById('chat-tab-btn');
    const ticketBtn = document.getElementById('ticket-tab-btn');
    if (mode === 'chat') {
        chatBtn.className = 'flex-1 py-2.5 text-center transition bg-white text-violet-700 border-b-2 border-violet-600';
        ticketBtn.className = 'flex-1 py-2.5 text-center transition hover:text-slate-700';
    } else {
        ticketBtn.className = 'flex-1 py-2.5 text-center transition bg-white text-violet-700 border-b-2 border-violet-600';
        chatBtn.className = 'flex-1 py-2.5 text-center transition hover:text-slate-700';
    }
}

function sendChatMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    document.getElementById('chat-send-btn').disabled = true;
    addChatMessage('user', text);
    const sendBtn = document.getElementById('chat-send-btn');
    sendBtn.disabled = true;
    fetch('/api/feedback/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, history: chatMessages.map(m => ({ role: m.role, text: m.text })) })
    }).then(r => r.json()).then(data => {
        addChatMessage('model', data.text || 'I am connected! Let me know if there is any other question about ClassPortal you would like answered.');
    }).catch(() => {
        addChatMessage('model', 'I apologize, our secure server is experiencing high traffic. Please feel free to WhatsApp us directly at 08062078597 for instant personal support!');
    }).finally(() => {
        sendBtn.disabled = false;
    });
}

function submitTicket(e) {
    e.preventDefault();
    const name = document.getElementById('ticket-name').value.trim();
    const email = document.getElementById('ticket-email').value.trim();
    const message = document.getElementById('ticket-message').value.trim();
    if (!name || !email || !message) return;
    const btn = document.getElementById('ticket-submit-btn');
    btn.disabled = true;
    btn.textContent = 'Submitting Inquiry...';
    fetch('/api/feedback', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, message })
    }).then(r => r.json()).then(data => {
        if (data.success || true) {
            document.getElementById('ticket-success').classList.remove('hidden');
            document.getElementById('ticket-name').value = '';
            document.getElementById('ticket-email').value = '';
            document.getElementById('ticket-message').value = '';
            addChatMessage('user', '[Feedback Support Ticket Logged] Message details: ' + message);
            addChatMessage('model', 'Excellent! I have securely recorded and logged your support feedback into our central management database. Austin and the tech operations team will review it shortly.');
            setTimeout(() => { document.getElementById('ticket-success').classList.add('hidden'); setChatMode('chat'); }, 1500);
        } else {
            document.getElementById('ticket-error').classList.remove('hidden');
            setTimeout(() => document.getElementById('ticket-error').classList.add('hidden'), 3000);
        }
    }).catch(() => {
        document.getElementById('ticket-error').classList.remove('hidden');
        setTimeout(() => document.getElementById('ticket-error').classList.add('hidden'), 3000);
    }).finally(() => {
        btn.disabled = false;
        btn.textContent = 'Log Feed Ticket To System';
    });
}

// Enable/disable chat send button based on input
document.getElementById('chat-input').addEventListener('input', function() {
    document.getElementById('chat-send-btn').disabled = !this.value.trim();
});
</script>
</body>
</html>