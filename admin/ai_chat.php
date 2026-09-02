<?php
require_once __DIR__ . '/bootstrap.php';
requirePermission('ai_use');

$active_tab = 'ai_chat';
$page_title = 'AI Assistant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - Minute Burger Admin</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/minute1/assets/css/admin.css">
    <style>
        /* ── Chat Page Layout ── */
        .chat-page-wrapper {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            padding: 1.25rem;
            gap: 0;
        }

        .chat-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: clip;
            min-height: 0;
        }

        /* ── Chat Header Bar ── */
        .chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: var(--bg-card);
            flex-shrink: 0;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chat-header-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .chat-header-info h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .chat-header-info span {
            font-size: 0.75rem;
            color: var(--green);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .chat-header-info span::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--green);
            display: inline-block;
        }

        .chat-clear-btn {
            background: none;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .chat-clear-btn:hover {
            background: var(--red-light);
            color: var(--red);
            border-color: var(--red);
        }

        /* ── Messages Area ── */
        .chat-messages {
            flex: 1 1 0;
            min-height: 0;
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            background: var(--bg);
            scroll-behavior: smooth;
            align-content: flex-start;
        }

        .chat-messages::-webkit-scrollbar {
            width: 5px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        /* ── Message Bubbles ── */
        .msg-row {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 0.5rem;
            max-width: 78%;
            flex-shrink: 0;
            margin-bottom: 1rem;
            animation: msgFadeUp 0.3s ease forwards;
        }

        .msg-row:last-child {
            margin-bottom: 0;
        }

        .msg-row.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .msg-row.bot {
            align-self: flex-start;
        }

        .msg-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .msg-row.bot .msg-avatar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }

        .msg-row.user .msg-avatar {
            background: var(--text-primary);
            color: #fff;
        }

        .msg-bubble {
            padding: 0.75rem 1rem;
            border-radius: 16px;
            line-height: 1.55;
            font-size: 0.9rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
            box-sizing: border-box;
        }

        .msg-row.user .msg-bubble {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .msg-row.bot .msg-bubble {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
        }

        .msg-bubble .formatted-content ul,
        .msg-bubble .formatted-content ol {
            padding-left: 1.25rem;
            margin: 0.4rem 0;
        }

        .msg-bubble .formatted-content li {
            margin-bottom: 0.2rem;
        }

        .msg-bubble .formatted-content strong {
            font-weight: 600;
        }

        .msg-row.user .msg-bubble .formatted-content strong {
            color: #fff;
        }

        .msg-time {
            font-size: 0.68rem;
            color: var(--text-muted);
            margin-top: 4px;
            padding: 0 0.25rem;
        }

        .msg-row.user .msg-time {
            text-align: right;
        }

        .msg-body {
            display: flex;
            flex-direction: column;
            flex: 0 1 auto;
            min-width: 0;
        }

        /* ── Typing Indicator ── */
        .typing-row {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 0.5rem;
            align-self: flex-start;
            max-width: 78%;
            flex-shrink: 0;
            margin-bottom: 1rem;
            animation: msgFadeUp 0.3s ease forwards;
        }

        .typing-row:last-child {
            margin-bottom: 0;
        }

        .typing-bubble {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            border-bottom-left-radius: 4px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .typing-bubble .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--text-muted);
            animation: typingBounce 1.4s infinite;
        }

        .typing-bubble .dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-bubble .dot:nth-child(3) { animation-delay: 0.4s; }

        .typing-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 3px;
            padding-left: 0.25rem;
        }

        @keyframes typingBounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-6px); opacity: 1; }
        }

        @keyframes msgFadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Suggestion Chips ── */
        .chips-area {
            padding: 0.625rem 1.25rem;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .chips-area.hidden {
            display: none;
        }

        .chip {
            background: var(--primary-light);
            border: 1px solid rgba(243, 121, 2, 0.15);
            color: var(--primary-dark);
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }

        .chip:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        /* ── Input Bar ── */
        .chat-input-bar {
            display: flex;
            align-items: flex-end;
            gap: 0.625rem;
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--border);
            background: var(--bg-card);
            flex-shrink: 0;
        }

        .chat-textarea {
            flex: 1;
            resize: none;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0.625rem 0.875rem;
            font-size: 0.9rem;
            font-family: inherit;
            line-height: 1.5;
            min-height: 42px;
            max-height: 120px;
            overflow-y: auto;
            color: var(--text-primary);
            background: var(--bg);
            transition: var(--transition);
        }

        .chat-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
            background: var(--bg-card);
        }

        .chat-textarea::placeholder {
            color: var(--text-muted);
        }

        .chat-textarea:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .send-btn {
            width: 42px;
            height: 42px;
            border: none;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            font-size: 1.15rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .send-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(243, 121, 2, 0.35);
        }

        .send-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
        }

        /* ── Welcome Message Styling ── */
        .welcome-list {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0;
        }

        .welcome-list li {
            padding: 0.15rem 0;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .chat-page-wrapper {
                padding: 0;
            }

            .chat-card {
                border-radius: 0;
            }

            .msg-row {
                max-width: 90%;
            }

            .chips-area {
                padding: 0.5rem 0.875rem;
                gap: 0.375rem;
            }

            .chip {
                font-size: 0.75rem;
                padding: 0.3rem 0.7rem;
            }

            .chat-input-bar {
                padding: 0.625rem 0.875rem;
            }

            .chat-header {
                padding: 0.75rem 0.875rem;
            }
        }

        @media (max-width: 480px) {
            .msg-row {
                max-width: 95%;
            }

            .msg-avatar {
                width: 28px;
                height: 28px;
                font-size: 0.8rem;
            }

            .msg-bubble {
                font-size: 0.85rem;
                padding: 0.625rem 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/includes/header.php'; ?>

            <div class="chat-page-wrapper">
                <div class="chat-card">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-header-left">
                            <div class="chat-header-avatar">
                                <i class='bx bx-bot'></i>
                            </div>
                            <div class="chat-header-info">
                                <h3>Minute Burger AI</h3>
                                <span>Online</span>
                            </div>
                        </div>
                        <button class="chat-clear-btn" onclick="clearChat()" title="Clear conversation">
                            <i class='bx bx-trash'></i> Clear
                        </button>
                    </div>

                    <!-- Messages -->
                    <div class="chat-messages" id="chatMessages"></div>

                    <!-- Suggestion Chips -->
                    <div class="chips-area" id="chipsArea">
                        <span class="chip" data-q="What are today's total sales and how do they compare to yesterday?">📊 Today's Sales Summary</span>
                        <span class="chip" data-q="Which inventory items are low in stock and need restocking?">📦 Low Stock Alert</span>
                        <span class="chip" data-q="What are the top selling products this month?">🏆 Top Selling Products</span>
                        <span class="chip" data-q="Show me the weekly sales trend and performance analysis">📈 Weekly Sales Trend</span>
                        <span class="chip" data-q="What items should I restock and in what quantities?">🔄 Restock Recommendations</span>
                        <span class="chip" data-q="Give me a full revenue analysis for this month">💰 Revenue Analysis</span>
                    </div>

                    <!-- Input Bar -->
                    <div class="chat-input-bar">
                        <textarea id="chatInput"
                                  class="chat-textarea"
                                  placeholder="Ask about sales, inventory, or business insights..."
                                  rows="1"></textarea>
                        <button class="send-btn" id="sendBtn" aria-label="Send message" onclick="sendMessage()" title="Send message">
                            <i class='bx bx-send'></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (() => {
        /* ── State ── */
        const STORAGE_KEY = 'mb_ai_chat_history';
        let isProcessing = false;

        /* ── DOM refs ── */
        const messagesEl = document.getElementById('chatMessages');
        const inputEl    = document.getElementById('chatInput');
        const sendBtn    = document.getElementById('sendBtn');
        const chipsArea  = document.getElementById('chipsArea');

        /* ── Init ── */
        loadHistory();
        if (getHistory().length === 0) {
            showWelcome();
        }
        updateChipsVisibility();

        /* ── Chip clicks ── */
        chipsArea.addEventListener('click', e => {
            const chip = e.target.closest('.chip');
            if (chip && !isProcessing) {
                sendMessage(chip.dataset.q);
            }
        });

        /* ── Textarea auto-resize + keyboard ── */
        inputEl.addEventListener('input', autoResize);
        inputEl.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (!isProcessing) sendMessage();
            }
        });

        function autoResize() {
            inputEl.style.height = 'auto';
            inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
        }

        /* ── Send Message ── */
        window.sendMessage = function(overrideText) {
            if (isProcessing) return;

            const text = (overrideText || inputEl.value).trim();
            if (!text) return;

            inputEl.value = '';
            inputEl.style.height = 'auto';

            appendMessage('user', text);
            saveHistory();
            setProcessing(true);
            showTyping();
            updateChipsVisibility();

            fetch('../ai/ai_endpoint.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question: text })
            })
            .then(r => r.json())
            .then(data => {
                removeTyping();
                const answer = (data.success && data.answer)
                    ? data.answer
                    : 'Sorry, I could not process your question. Please try again.';
                appendMessage('bot', answer);
                saveHistory();
                setProcessing(false);
                updateChipsVisibility();
            })
            .catch(() => {
                removeTyping();
                appendMessage('bot', 'Sorry, I encountered a connection error. Please check your network and try again.');
                saveHistory();
                setProcessing(false);
                updateChipsVisibility();
            });
        };

        /* ── Clear Chat ── */
        window.clearChat = function() {
            sessionStorage.removeItem(STORAGE_KEY);
            messagesEl.innerHTML = '';
            showWelcome();
            updateChipsVisibility();
        };

        /* ── Helpers ── */
        function setProcessing(state) {
            isProcessing = state;
            inputEl.disabled = state;
            sendBtn.disabled = state;
            if (!state) inputEl.focus();
        }

        function updateChipsVisibility() {
            const msgs = messagesEl.querySelectorAll('.msg-row');
            /* Show chips when empty (only welcome) or after bot finishes responding */
            chipsArea.classList.toggle('hidden', isProcessing);
        }

        function now() {
            return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        /* ── Welcome ── */
        function showWelcome() {
            const welcomeHtml = `
                <div>👋 Hi! I'm your <strong>Minute Burger AI Assistant</strong>. I can help you with:</div>
                <ul class="welcome-list">
                    <li>&#8226; Sales analysis and trends</li>
                    <li>&#8226; Inventory management insights</li>
                    <li>&#8226; Product performance</li>
                    <li>&#8226; Restocking recommendations</li>
                </ul>
                <div>Ask me anything about your business!</div>`;
            appendMessage('bot', welcomeHtml, true);
        }

        /* ── Format AI Text ── */
        function formatResponse(text) {
            let s = text;

            // Escape HTML first
            s = s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            // Bold: **text** or __text__
            s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            s = s.replace(/__(.+?)__/g, '<strong>$1</strong>');

            // Peso formatting
            s = s.replace(/₱([\d,]+\.?\d*)/g, '<strong>₱$1</strong>');

            // Bullet lists: lines starting with - or bullet
            const lines = s.split('\n');
            let inList = false;
            let result = [];

            for (let i = 0; i < lines.length; i++) {
                const trimmed = lines[i].trim();
                const isBullet = /^[-•]\s+/.test(trimmed);

                if (isBullet) {
                    if (!inList) { result.push('<ul>'); inList = true; }
                    result.push('<li>' + trimmed.replace(/^[-•]\s+/, '') + '</li>');
                } else {
                    if (inList) { result.push('</ul>'); inList = false; }
                    if (trimmed === '') {
                        result.push('<br>');
                    } else {
                        result.push(trimmed);
                    }
                }
            }
            if (inList) result.push('</ul>');

            return result.join('\n').replace(/\n(?!<)/g, '<br>').replace(/\n/g, '');
        }

        /* ── Append Message ── */
        function appendMessage(role, text, isHtml = false) {
            const row = document.createElement('div');
            row.className = `msg-row ${role}`;

            const avatarIcon = role === 'user' ? 'bx-user' : 'bx-bot';
            const formatted  = isHtml ? text : formatResponse(text);

            row.innerHTML = `
                <div class="msg-avatar"><i class='bx ${avatarIcon}'></i></div>
                <div class="msg-body">
                    <div class="msg-bubble"><div class="formatted-content">${formatted}</div></div>
                    <div class="msg-time">${now()}</div>
                </div>`;

            messagesEl.appendChild(row);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        /* ── Typing Indicator ── */
        function showTyping() {
            const el = document.createElement('div');
            el.className = 'typing-row';
            el.id = 'typingIndicator';
            el.innerHTML = `
                <div class="msg-avatar" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;">
                    <i class='bx bx-bot'></i>
                </div>
                <div>
                    <div class="typing-bubble">
                        <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                    </div>
                    <div class="typing-label">AI is thinking...</div>
                </div>`;
            messagesEl.appendChild(el);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function removeTyping() {
            const el = document.getElementById('typingIndicator');
            if (el) el.remove();
        }

        /* ── Session Storage ── */
        function getHistory() {
            try {
                return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
            } catch { return []; }
        }

        function saveHistory() {
            const rows = messagesEl.querySelectorAll('.msg-row');
            const history = [];
            rows.forEach(row => {
                const role = row.classList.contains('user') ? 'user' : 'bot';
                const contentEl = row.querySelector('.formatted-content');
                const timeEl = row.querySelector('.msg-time');
                if (contentEl) {
                    history.push({
                        role,
                        html: contentEl.innerHTML,
                        time: timeEl ? timeEl.textContent : ''
                    });
                }
            });
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history));
        }

        function loadHistory() {
            const history = getHistory();
            if (history.length === 0) return;

            history.forEach(msg => {
                const row = document.createElement('div');
                row.className = `msg-row ${msg.role}`;
                const avatarIcon = msg.role === 'user' ? 'bx-user' : 'bx-bot';

                row.innerHTML = `
                    <div class="msg-avatar"><i class='bx ${avatarIcon}'></i></div>
                    <div class="msg-body">
                        <div class="msg-bubble"><div class="formatted-content">${msg.html}</div></div>
                        <div class="msg-time">${msg.time}</div>
                    </div>`;
                row.style.animation = 'none';
                messagesEl.appendChild(row);
            });
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    })();
    </script>
</body>
</html>
