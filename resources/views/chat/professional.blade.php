<!-- PIZI AI CHAT WIDGET -->
<style>
    #pziChatRoot * { box-sizing: border-box; }

    .chat-bubble-wrapper {
        position: fixed;
        bottom: 20px;
        left: 20px;
        z-index: 99999;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        pointer-events: auto;
    }

    /* ===== Animated AI robot bubble ===== */
    .chat-bubble {
        width: 62px;
        height: 62px;
        background: linear-gradient(135deg, #FF6B5B 0%, #ED4E3D 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(255, 107, 91, 0.45);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        border: none;
        position: relative;
    }

    .chat-bubble::before {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        border: 2px solid rgba(255, 107, 91, 0.5);
        animation: pziRing 2.4s ease-out infinite;
    }

    @keyframes pziRing {
        0%   { transform: scale(0.85); opacity: 0.8; }
        100% { transform: scale(1.35); opacity: 0; }
    }

    .chat-bubble:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 30px rgba(255, 107, 91, 0.6);
    }

    .pzi-bot-icon {
        width: 32px;
        height: 32px;
        position: relative;
        z-index: 1;
        animation: pziBotFloat 3s ease-in-out infinite;
    }

    @keyframes pziBotFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }

    .pzi-bot-eye {
        animation: pziBlink 3.5s ease-in-out infinite;
        transform-origin: center;
    }

    @keyframes pziBlink {
        0%, 92%, 100% { transform: scaleY(1); }
        96% { transform: scaleY(0.1); }
    }

    .chat-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #10B981;
        color: white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        border: 2px solid white;
        z-index: 2;
    }

    /* ===== Chat window ===== */
    .chat-window {
        position: fixed;
        bottom: 96px;
        left: 20px;
        width: 380px;
        height: 560px;
        max-height: calc(100vh - 130px);
        background: #fefcf6;
        border-radius: 20px;
        box-shadow: 0 10px 50px rgba(15, 39, 72, 0.25);
        display: none;
        flex-direction: column;
        z-index: 99999;
        overflow: hidden;
        animation: slideUp 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .chat-window.active { display: flex; }

    .chat-header {
        background: linear-gradient(135deg, #0F2748 0%, #1a3a52 100%);
        color: white;
        padding: 14px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        position: relative;
    }

    .chat-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .status-indicator {
        width: 8px;
        height: 8px;
        background: #4CAF50;
        border-radius: 50%;
        animation: pulse 2s infinite;
        flex-shrink: 0;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(76, 175, 80, 0); }
        100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
    }

    .close-btn {
        background: rgba(255,255,255,0.12);
        border: none;
        color: white;
        font-size: 16px;
        cursor: pointer;
        padding: 0;
        border-radius: 8px;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
    }

    .close-btn:hover { background: rgba(255,255,255,0.25); }

    .lang-selector {
        background: white;
        padding: 8px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid rgba(15,39,72,0.08);
        flex-shrink: 0;
    }

    .lang-selector label { font-size: 12px; font-weight: 600; color: #666; margin: 0; }

    .lang-selector select {
        padding: 5px 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        font-size: 12px;
    }

    .chat-body {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: #fefcf6;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .msg {
        padding: 10px 14px;
        border-radius: 14px;
        max-width: 85%;
        font-size: 13.5px;
        line-height: 1.5;
        word-wrap: break-word;
        white-space: pre-wrap;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .msg-bot {
        background: white;
        color: #0F2748;
        border: 1px solid rgba(15,39,72,0.08);
        border-bottom-left-radius: 4px;
        align-self: flex-start;
    }

    .msg-user {
        background: linear-gradient(135deg, #FF6B5B 0%, #ED4E3D 100%);
        color: white;
        border-bottom-right-radius: 4px;
        align-self: flex-end;
    }

    .msg-greeting {
        background: linear-gradient(135deg, #0F2748 0%, #1a3a52 100%);
        color: white;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }

    .typing {
        display: flex;
        gap: 4px;
        padding: 12px 14px;
        background: white;
        border: 1px solid rgba(15,39,72,0.08);
        border-radius: 14px;
        border-bottom-left-radius: 4px;
        width: fit-content;
        align-self: flex-start;
    }

    .typing span {
        width: 6px;
        height: 6px;
        background: #FF6B5B;
        border-radius: 50%;
        animation: typing 1.4s infinite;
    }

    .typing span:nth-child(2) { animation-delay: 0.2s; }
    .typing span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typing {
        0%, 60%, 100% { opacity: 0.3; transform: translateY(0); }
        30% { opacity: 1; transform: translateY(-3px); }
    }

    .chat-footer {
        display: flex;
        gap: 8px;
        padding: 12px 14px;
        background: white;
        border-top: 1px solid rgba(15,39,72,0.08);
        flex-shrink: 0;
    }

    .chat-footer input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid #ddd;
        border-radius: 20px;
        font-size: 13px;
        font-family: inherit;
    }

    .chat-footer input:focus {
        outline: none;
        border-color: #FF6B5B;
    }

    .chat-footer button {
        width: 38px;
        height: 38px;
        flex-shrink: 0;
        background: linear-gradient(135deg, #FF6B5B 0%, #ED4E3D 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-weight: 600;
        transition: transform 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chat-footer button:hover { transform: scale(1.08); }
    .chat-footer button:disabled { opacity: 0.5; cursor: not-allowed; }

    /* ===== Responsive ===== */
    @media (max-width: 480px) {
        .chat-bubble-wrapper { left: 12px; bottom: 12px; }
        .chat-bubble { width: 56px; height: 56px; }
        .chat-window {
            width: calc(100vw - 24px);
            height: calc(100vh - 100px);
            max-height: none;
            bottom: 78px;
            left: 12px;
            border-radius: 18px;
        }
    }
</style>

<div id="pziChatRoot">
<!-- CHAT BUBBLE -->
<div class="chat-bubble-wrapper">
    <button class="chat-bubble" id="chat-bubble" onclick="toggleChat()" aria-label="Open Pizi AI Assistant">
        <svg class="pzi-bot-icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="10" y="16" width="28" height="22" rx="9" fill="white"/>
            <circle class="pzi-bot-eye" cx="19" cy="27" r="3" fill="#FF6B5B"/>
            <circle class="pzi-bot-eye" cx="29" cy="27" r="3" fill="#FF6B5B"/>
            <rect x="21" y="33" width="6" height="2" rx="1" fill="#FF6B5B" opacity="0.6"/>
            <rect x="22" y="7" width="4" height="9" rx="2" fill="white"/>
            <circle cx="24" cy="6" r="3" fill="white"/>
            <rect x="4" y="24" width="5" height="8" rx="2.5" fill="white"/>
            <rect x="39" y="24" width="5" height="8" rx="2.5" fill="white"/>
        </svg>
        <span class="chat-badge"></span>
    </button>

    <!-- CHAT WINDOW -->
    <div class="chat-window" id="chat-window">
        <div class="chat-header">
            <h3>🤖 Pizi AI Assistant <span class="status-indicator"></span></h3>
            <button class="close-btn" onclick="closeChat()" title="Close Chat" aria-label="Close">✕</button>
        </div>
        <div class="lang-selector">
            <label for="lang-select">Language:</label>
            <select id="lang-select" onchange="changeLang()">
                <option value="en">🇬🇧 English</option>
                <option value="hi" selected>🇮🇳 हिंदी</option>
            </select>
        </div>
        <div class="chat-body" id="chat-body">
            <div class="msg msg-greeting" id="greeting-msg">
                नमस्ते! 👋 मैं आपकी कैसे मदद कर सकता हूं?
            </div>
        </div>
        <div class="chat-footer">
            <input
                type="text"
                id="msg-input"
                placeholder="संदेश लिखें..."
                onkeypress="if(event.key==='Enter') sendMsg()"
            />
            <button id="send-btn" onclick="sendMsg()" aria-label="Send">➤</button>
        </div>
    </div>
</div>
</div>

<script>
(function () {
    let currentLang = 'hi';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    // Persistent per-browser session id (survives page navigation/reload)
    let pziSessionId = localStorage.getItem('pizi_chat_session_id');
    if (!pziSessionId) {
        pziSessionId = 'pzi_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
        localStorage.setItem('pizi_chat_session_id', pziSessionId);
    }

    // In-memory conversation history for this tab — gives the AI context
    // for follow-up questions ("what about under 10k?")
    let pziHistory = [];
    try { pziHistory = JSON.parse(sessionStorage.getItem('pizi_chat_history') || '[]'); } catch (e) { pziHistory = []; }

    const greetings = { hi: 'नमस्ते! 👋 मैं आपकी कैसे मदद कर सकता हूं?', en: 'Hello! 👋 How can I help you today?' };
    const placeholders = { hi: 'संदेश लिखें...', en: 'Type your message...' };

    window.toggleChat = function () {
        const chatWindow = document.getElementById('chat-window');
        chatWindow.classList.toggle('active');
        if (chatWindow.classList.contains('active')) {
            document.getElementById('msg-input').focus();
        }
    };

    window.closeChat = function () {
        document.getElementById('chat-window').classList.remove('active');
    };

    window.changeLang = function () {
        currentLang = document.getElementById('lang-select').value;
        document.getElementById('msg-input').placeholder = placeholders[currentLang];
        document.getElementById('greeting-msg').textContent = greetings[currentLang];
    };

    function showTyping() {
        const chatBody = document.getElementById('chat-body');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'typing';
        typingDiv.id = 'typing-indicator';
        typingDiv.innerHTML = '<span></span><span></span><span></span>';
        chatBody.appendChild(typingDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function removeTyping() {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();
    }

    window.sendMsg = async function () {
        const input = document.getElementById('msg-input');
        const sendBtn = document.getElementById('send-btn');
        const msg = input.value.trim();
        if (!msg) return;

        const chatBody = document.getElementById('chat-body');
        const userMsg = document.createElement('div');
        userMsg.className = 'msg msg-user';
        userMsg.textContent = msg;
        chatBody.appendChild(userMsg);

        input.value = '';
        sendBtn.disabled = true;
        chatBody.scrollTop = chatBody.scrollHeight;
        showTyping();

        try {
            const response = await fetch('/api/chat/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    message: msg,
                    language: currentLang,
                    session_id: pziSessionId,
                    history: pziHistory.slice(-8),
                })
            });

            const data = await response.json();
            removeTyping();

            if (data.success) {
                const botMsg = document.createElement('div');
                botMsg.className = 'msg msg-bot';
                botMsg.textContent = data.message;
                chatBody.appendChild(botMsg);

                pziHistory.push({ sender: 'user', message: msg });
                pziHistory.push({ sender: 'bot', message: data.message });
                sessionStorage.setItem('pizi_chat_history', JSON.stringify(pziHistory.slice(-20)));
            }

            chatBody.scrollTop = chatBody.scrollHeight;
        } catch (error) {
            removeTyping();
            const errorMsg = document.createElement('div');
            errorMsg.className = 'msg msg-bot';
            errorMsg.textContent = currentLang === 'hi' ? 'कोशिश करें फिर से। 🙏' : 'Please try again. 🙏';
            chatBody.appendChild(errorMsg);
        }

        sendBtn.disabled = false;
    };
})();
</script>
