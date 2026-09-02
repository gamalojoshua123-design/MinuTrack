<?php
/**
 * Floating AI Chatbot Widget + Low Stock Notification Toast
 * Include this file before </body> on every admin page.
 */

// Get low stock items for the notification
$lowStockItems = [];
$lowStockCount = 0;
if (isset($pdo)) {
    try {
        $stmtLow = $pdo->prepare("
            SELECT item_name, quantity, min_stock
            FROM inventory
            WHERE quantity < min_stock
              AND (status IS NULL OR status = 'active')
              AND deleted_at IS NULL
            ORDER BY (quantity / min_stock) ASC
            LIMIT 10
        ");
        $stmtLow->execute();
        $lowStockItems = $stmtLow->fetchAll(PDO::FETCH_ASSOC);
        $lowStockCount = count($lowStockItems);
    } catch (Exception $e) {
        // Silently fail
    }
}
?>

<!-- ============================================ -->
<!-- Low Stock Notification Toast                 -->
<!-- ============================================ -->
<div class="stock-notification" id="stockNotification" style="display:none;">
    <div class="stock-notif-icon"><i class='bx bx-error-circle'></i></div>
    <div class="stock-notif-content">
        <div class="stock-notif-title">Low Stock Alert</div>
        <div class="stock-notif-text" id="stockNotifText"></div>
    </div>
    <button class="stock-notif-close" aria-label="Dismiss stock alert" onclick="dismissStockNotif()"><i class='bx bx-x'></i></button>
</div>

<!-- ============================================ -->
<!-- Floating AI Chatbot Widget                   -->
<!-- ============================================ -->
<div id="aiChatWidget" class="ai-chat-widget">
    <!-- Floating Button -->
    <button class="ai-chat-fab" id="aiChatFab" aria-label="Toggle AI chat" onclick="toggleAIChat()">
        <i class='bx bx-bot' id="aiFabIcon"></i>
        <span class="ai-fab-badge" id="aiFabBadge" style="display:none;"></span>
    </button>

    <!-- Chat Panel -->
    <div class="ai-chat-panel" id="aiChatPanel" style="display:none;">
        <div class="ai-chat-header">
            <div class="ai-chat-header-left">
                <div class="ai-avatar"><i class='bx bx-bot'></i></div>
                <div>
                    <div class="ai-chat-title">AI Assistant</div>
                    <div class="ai-chat-status"><span class="ai-status-dot"></span> Online</div>
                </div>
            </div>
            <div class="ai-chat-header-right">
                <button onclick="clearAIChat()" aria-label="Clear chat" title="Clear chat"><i class='bx bx-trash'></i></button>
                <button onclick="toggleAIChat()" aria-label="Close AI chat" title="Close"><i class='bx bx-x'></i></button>
            </div>
        </div>

        <div class="ai-chat-messages" id="aiChatMessages">
            <!-- Welcome message injected by JS -->
        </div>

        <div class="ai-chat-suggestions" id="aiChatSuggestions">
            <button onclick="sendAIMessage('What are today\'s total sales?')">&#x1F4CA; Sales</button>
            <button onclick="sendAIMessage('Which items are low in stock?')">&#x1F4E6; Low Stock</button>
            <button onclick="sendAIMessage('What are the top selling products?')">&#x1F3C6; Top Products</button>
            <button onclick="sendAIMessage('Give me a weekly sales summary')">&#x1F4C8; Weekly</button>
        </div>

        <div class="ai-chat-input-area">
            <textarea id="aiChatInput" placeholder="Ask anything about your business..." rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendAIMessage();}"></textarea>
            <button class="ai-send-btn" id="aiSendBtn" aria-label="Send message" onclick="sendAIMessage()">
                <i class='bx bx-send'></i>
            </button>
        </div>
    </div>
</div>

<style>
/* ============================================ */
/* Low Stock Notification Toast                 */
/* ============================================ */
.stock-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #ff6b35, #e74c3c);
    color: #fff;
    padding: 14px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 8px 32px rgba(231, 76, 60, 0.35);
    z-index: 10000;
    max-width: 420px;
    animation: stockSlideIn 0.4s ease forwards;
    font-family: inherit;
}

.stock-notification.hiding {
    animation: stockSlideOut 0.4s ease forwards;
}

@keyframes stockSlideIn {
    from { opacity: 0; transform: translateX(100px); }
    to   { opacity: 1; transform: translateX(0); }
}

@keyframes stockSlideOut {
    from { opacity: 1; transform: translateX(0); }
    to   { opacity: 0; transform: translateX(100px); }
}

.stock-notif-icon {
    font-size: 1.6rem;
    flex-shrink: 0;
    display: flex;
    align-items: center;
}

.stock-notif-content {
    flex: 1;
}

.stock-notif-title {
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 2px;
}

.stock-notif-text {
    font-size: 0.82rem;
    opacity: 0.95;
    line-height: 1.4;
}

.stock-notif-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    transition: background 0.2s;
}

.stock-notif-close:hover {
    background: rgba(255,255,255,0.35);
}

/* ============================================ */
/* Floating AI Chatbot Widget                   */
/* ============================================ */
.ai-chat-widget {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* FAB Button */
.ai-chat-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #f37902, #d96800);
    color: #fff;
    font-size: 1.6rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(243, 121, 2, 0.45);
    z-index: 9998;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    animation: fabPulse 2s ease-in-out 1;
}

.ai-chat-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 28px rgba(243, 121, 2, 0.55);
}

@keyframes fabPulse {
    0%, 100% { box-shadow: 0 4px 20px rgba(243, 121, 2, 0.45); }
    50% { box-shadow: 0 4px 30px rgba(243, 121, 2, 0.7), 0 0 0 10px rgba(243, 121, 2, 0.12); }
}

.ai-fab-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #e74c3c;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid #fff;
}

/* Chat Panel */
.ai-chat-panel {
    position: fixed;
    bottom: 90px;
    right: 24px;
    width: 380px;
    height: 520px;
    max-height: 70vh;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 12px 48px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.04);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
}

.ai-chat-panel.ai-panel-opening {
    animation: aiPanelSlideUp 0.3s ease forwards;
}

.ai-chat-panel.ai-panel-closing {
    animation: aiPanelSlideDown 0.25s ease forwards;
}

@keyframes aiPanelSlideUp {
    from { opacity: 0; transform: scale(0.85) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes aiPanelSlideDown {
    from { opacity: 1; transform: scale(1) translateY(0); }
    to   { opacity: 0; transform: scale(0.85) translateY(20px); }
}

/* Chat Header */
.ai-chat-header {
    background: linear-gradient(135deg, #f37902, #d96800);
    color: #fff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    border-radius: 16px 16px 0 0;
}

.ai-chat-header-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ai-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.ai-chat-title {
    font-weight: 700;
    font-size: 0.95rem;
    line-height: 1.2;
}

.ai-chat-status {
    font-size: 0.72rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 5px;
}

.ai-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #4ade80;
    display: inline-block;
    animation: statusPulse 2s infinite;
}

@keyframes statusPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.ai-chat-header-right {
    display: flex;
    gap: 4px;
}

.ai-chat-header-right button {
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    transition: background 0.2s;
}

.ai-chat-header-right button:hover {
    background: rgba(255,255,255,0.3);
}

/* Messages Area */
.ai-chat-messages {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    background: #fafafa;
    scroll-behavior: smooth;
    align-content: flex-start;
}

.ai-chat-messages::-webkit-scrollbar {
    width: 4px;
}

.ai-chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.ai-chat-messages::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 4px;
}

/* Message Rows */
.ai-msg-row {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 8px;
    max-width: 80%;
    flex-shrink: 0;
    margin-bottom: 10px;
    animation: aiMsgFadeUp 0.25s ease forwards;
}

.ai-msg-row:last-child {
    margin-bottom: 0;
}

.ai-msg-row.user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.ai-msg-row.bot {
    align-self: flex-start;
}

.ai-msg-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.ai-msg-row.bot .ai-msg-avatar {
    background: linear-gradient(135deg, #f37902, #d96800);
    color: #fff;
}

.ai-msg-row.user .ai-msg-avatar {
    background: #374151;
    color: #fff;
}

.ai-msg-bubble {
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 0.85rem;
    line-height: 1.5;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
    box-sizing: border-box;
}

.ai-msg-row.user .ai-msg-bubble {
    background: linear-gradient(135deg, #f37902, #d96800);
    color: #fff;
    border-bottom-right-radius: 4px;
}

.ai-msg-row.bot .ai-msg-bubble {
    background: #f5f5f5;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
}

.ai-msg-bubble .ai-formatted ul,
.ai-msg-bubble .ai-formatted ol {
    padding-left: 1.1rem;
    margin: 0.3rem 0;
}

.ai-msg-bubble .ai-formatted li {
    margin-bottom: 0.15rem;
}

.ai-msg-bubble .ai-formatted strong {
    font-weight: 600;
}

.ai-msg-time {
    font-size: 0.62rem;
    color: #9ca3af;
    margin-top: 3px;
    padding: 0 4px;
}

.ai-msg-row.user .ai-msg-time {
    text-align: right;
}

.ai-msg-body {
    display: flex;
    flex-direction: column;
    flex: 0 1 auto;
    min-width: 0;
}

/* Typing Indicator */
.ai-typing-row {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 8px;
    align-self: flex-start;
    max-width: 80%;
    flex-shrink: 0;
    margin-bottom: 10px;
    animation: aiMsgFadeUp 0.25s ease forwards;
}

.ai-typing-row:last-child {
    margin-bottom: 0;
}

.ai-typing-bubble {
    background: #f5f5f5;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    border-bottom-left-radius: 4px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ai-typing-bubble .ai-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #9ca3af;
    animation: aiTypingBounce 1.4s infinite;
}

.ai-typing-bubble .ai-dot:nth-child(2) { animation-delay: 0.2s; }
.ai-typing-bubble .ai-dot:nth-child(3) { animation-delay: 0.4s; }

.ai-typing-label {
    font-size: 0.65rem;
    color: #9ca3af;
    margin-top: 2px;
    padding-left: 4px;
}

@keyframes aiTypingBounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-5px); opacity: 1; }
}

@keyframes aiMsgFadeUp {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Suggestion Chips */
.ai-chat-suggestions {
    padding: 8px 14px;
    background: #fff;
    border-top: 1px solid #f0f0f0;
    display: flex;
    gap: 6px;
    overflow-x: auto;
    flex-shrink: 0;
    -webkit-overflow-scrolling: touch;
}

.ai-chat-suggestions::-webkit-scrollbar {
    height: 0;
}

.ai-chat-suggestions button {
    background: #fff4ec;
    border: 1px solid rgba(243, 121, 2, 0.15);
    color: #c05600;
    padding: 5px 12px;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
    font-family: inherit;
}

.ai-chat-suggestions button:hover {
    background: #f37902;
    color: #fff;
    border-color: #f37902;
}

/* Input Area */
.ai-chat-input-area {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 10px 14px;
    border-top: 1px solid #e5e7eb;
    background: #fff;
    flex-shrink: 0;
    border-radius: 0 0 16px 16px;
}

.ai-chat-input-area textarea {
    flex: 1;
    resize: none;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 0.85rem;
    font-family: inherit;
    line-height: 1.5;
    min-height: 38px;
    max-height: 90px;
    overflow-y: auto;
    color: #1f2937;
    background: #fafafa;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
}

.ai-chat-input-area textarea:focus {
    border-color: #f37902;
    box-shadow: 0 0 0 3px rgba(243, 121, 2, 0.1);
    background: #fff;
}

.ai-chat-input-area textarea::placeholder {
    color: #9ca3af;
}

.ai-chat-input-area textarea:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ai-send-btn {
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #f37902, #d96800);
    color: #fff;
    font-size: 1.05rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.ai-send-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(243, 121, 2, 0.35);
}

.ai-send-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

/* Welcome list styling */
.ai-msg-bubble .ai-welcome-list {
    list-style: none;
    padding: 0;
    margin: 0.35rem 0;
}

.ai-msg-bubble .ai-welcome-list li {
    padding: 0.1rem 0;
    font-size: 0.83rem;
}

/* Responsive: Mobile */
@media (max-width: 600px) {
    .ai-chat-panel {
        width: calc(100vw - 16px);
        right: 8px;
        bottom: 80px;
        height: 85vh;
        max-height: 85vh;
        border-radius: 14px;
    }

    .ai-chat-fab {
        bottom: 16px;
        right: 16px;
        width: 50px;
        height: 50px;
        font-size: 1.4rem;
    }

    .ai-msg-row {
        max-width: 90%;
    }

    .stock-notification {
        right: 8px;
        left: 8px;
        max-width: none;
    }
}
</style>

<script>
(function() {
    /* ============================================ */
    /* Low Stock Notification System                */
    /* ============================================ */
    var lowStockCount = <?php echo (int)$lowStockCount; ?>;
    var lowStockNames = <?php echo json_encode(array_map(function($i) {
        return $i['item_name'] . ' (' . (int)$i['quantity'] . '/' . (int)$i['min_stock'] . ')';
    }, array_slice($lowStockItems, 0, 5))); ?>;

    function showStockNotif() {
        if (lowStockCount === 0) return;
        if (sessionStorage.getItem('mb_stock_notif_shown')) return;

        sessionStorage.setItem('mb_stock_notif_shown', '1');
        var el = document.getElementById('stockNotification');
        var textEl = document.getElementById('stockNotifText');

        var msg = '\u26A0\uFE0F ' + lowStockCount + ' item' + (lowStockCount > 1 ? 's are' : ' is') + ' running low on stock';
        if (lowStockNames.length > 0) {
            msg += ': ' + lowStockNames.join(', ');
        }
        textEl.textContent = msg;
        el.style.display = 'flex';

        // Auto-dismiss after 5 seconds
        setTimeout(function() { dismissStockNotif(); }, 5000);
    }

    window.dismissStockNotif = function() {
        var el = document.getElementById('stockNotification');
        if (!el || el.style.display === 'none') return;
        el.classList.add('hiding');
        setTimeout(function() {
            el.style.display = 'none';
            el.classList.remove('hiding');
        }, 400);
    };

    // Show notification on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(showStockNotif, 800); });
    } else {
        setTimeout(showStockNotif, 800);
    }

    /* ============================================ */
    /* Floating AI Chatbot Widget                   */
    /* ============================================ */
    var WIDGET_STORAGE_KEY = 'mb_ai_widget_history';
    var WIDGET_OPEN_KEY = 'mb_ai_widget_open';
    var aiIsProcessing = false;
    var aiPanelOpen = false;

    var aiPanel = document.getElementById('aiChatPanel');
    var aiFab = document.getElementById('aiChatFab');
    var aiFabIcon = document.getElementById('aiFabIcon');
    var aiMessages = document.getElementById('aiChatMessages');
    var aiInput = document.getElementById('aiChatInput');
    var aiSendBtn = document.getElementById('aiSendBtn');
    var aiSuggestions = document.getElementById('aiChatSuggestions');

    /* ── Toggle Chat Panel ── */
    window.toggleAIChat = function() {
        if (aiPanelOpen) {
            closeAIPanel();
        } else {
            openAIPanel();
        }
    };

    function openAIPanel() {
        aiPanelOpen = true;
        aiPanel.style.display = 'flex';
        aiPanel.classList.remove('ai-panel-closing');
        aiPanel.classList.add('ai-panel-opening');
        aiFabIcon.className = 'bx bx-x';
        sessionStorage.setItem(WIDGET_OPEN_KEY, '1');

        // Load history or show welcome
        if (aiMessages.children.length === 0) {
            loadAIHistory();
            if (getAIHistory().length === 0) {
                showAIWelcome();
            }
        }
        setTimeout(function() { aiInput.focus(); }, 300);
        scrollAIMessages();
    }

    function closeAIPanel() {
        aiPanelOpen = false;
        aiPanel.classList.remove('ai-panel-opening');
        aiPanel.classList.add('ai-panel-closing');
        aiFabIcon.className = 'bx bx-bot';
        sessionStorage.setItem(WIDGET_OPEN_KEY, '0');
        setTimeout(function() {
            aiPanel.style.display = 'none';
            aiPanel.classList.remove('ai-panel-closing');
        }, 250);
    }

    /* ── Send Message ── */
    window.sendAIMessage = function(overrideText) {
        if (aiIsProcessing) return;

        var text = (overrideText || aiInput.value || '').trim();
        if (!text) return;

        aiInput.value = '';
        aiInput.style.height = 'auto';

        appendAIMessage('user', text);
        saveAIHistory();
        setAIProcessing(true);
        showAITyping();

        // Determine base URL for ai_endpoint.php
        var endpointUrl = '/minute1/ai/ai_endpoint.php';

        fetch(endpointUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: text })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            removeAITyping();
            var answer = (data.success && data.answer)
                ? data.answer
                : 'Sorry, I could not process your question. Please try again.';
            appendAIMessage('bot', answer);
            saveAIHistory();
            setAIProcessing(false);
        })
        .catch(function() {
            removeAITyping();
            appendAIMessage('bot', 'Sorry, I encountered a connection error. Please try again.');
            saveAIHistory();
            setAIProcessing(false);
        });
    };

    /* ── Clear Chat ── */
    window.clearAIChat = function() {
        sessionStorage.removeItem(WIDGET_STORAGE_KEY);
        aiMessages.innerHTML = '';
        showAIWelcome();
    };

    /* ── Helpers ── */
    function setAIProcessing(state) {
        aiIsProcessing = state;
        aiInput.disabled = state;
        aiSendBtn.disabled = state;
        if (!state && aiPanelOpen) aiInput.focus();
    }

    function aiNow() {
        return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function scrollAIMessages() {
        aiMessages.scrollTop = aiMessages.scrollHeight;
    }

    /* ── Welcome Message ── */
    function showAIWelcome() {
        var html = '<div>\uD83D\uDC4B Hi! I\'m your <strong>AI Assistant</strong>. I can help with:</div>' +
            '<ul class="ai-welcome-list">' +
            '<li>\u2022 Sales analysis and trends</li>' +
            '<li>\u2022 Inventory management insights</li>' +
            '<li>\u2022 Product performance</li>' +
            '<li>\u2022 Restocking recommendations</li>' +
            '</ul>' +
            '<div>Ask me anything about your business!</div>';
        appendAIMessage('bot', html, true);
    }

    /* ── Format AI Response ── */
    function formatAIResponse(text) {
        var s = text;
        s = s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        s = s.replace(/__(.+?)__/g, '<strong>$1</strong>');
        s = s.replace(/\u20B1([\d,]+\.?\d*)/g, '<strong>\u20B1$1</strong>');

        var lines = s.split('\n');
        var inList = false;
        var result = [];

        for (var i = 0; i < lines.length; i++) {
            var trimmed = lines[i].trim();
            var isBullet = /^[-\u2022]\s+/.test(trimmed);

            if (isBullet) {
                if (!inList) { result.push('<ul>'); inList = true; }
                result.push('<li>' + trimmed.replace(/^[-\u2022]\s+/, '') + '</li>');
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
    function appendAIMessage(role, text, isHtml) {
        var row = document.createElement('div');
        row.className = 'ai-msg-row ' + role;

        var avatarIcon = role === 'user' ? 'bx-user' : 'bx-bot';
        var formatted = isHtml ? text : formatAIResponse(text);

        row.innerHTML =
            '<div class="ai-msg-avatar"><i class="bx ' + avatarIcon + '"></i></div>' +
            '<div class="ai-msg-body">' +
                '<div class="ai-msg-bubble"><div class="ai-formatted">' + formatted + '</div></div>' +
                '<div class="ai-msg-time">' + aiNow() + '</div>' +
            '</div>';

        aiMessages.appendChild(row);
        scrollAIMessages();
    }

    /* ── Typing Indicator ── */
    function showAITyping() {
        var el = document.createElement('div');
        el.className = 'ai-typing-row';
        el.id = 'aiTypingIndicator';
        el.innerHTML =
            '<div class="ai-msg-avatar" style="background:linear-gradient(135deg,#f37902,#d96800);color:#fff;">' +
                '<i class="bx bx-bot"></i>' +
            '</div>' +
            '<div>' +
                '<div class="ai-typing-bubble">' +
                    '<div class="ai-dot"></div><div class="ai-dot"></div><div class="ai-dot"></div>' +
                '</div>' +
                '<div class="ai-typing-label">AI is thinking...</div>' +
            '</div>';
        aiMessages.appendChild(el);
        scrollAIMessages();
    }

    function removeAITyping() {
        var el = document.getElementById('aiTypingIndicator');
        if (el) el.remove();
    }

    /* ── Session Storage ── */
    function getAIHistory() {
        try {
            return JSON.parse(sessionStorage.getItem(WIDGET_STORAGE_KEY)) || [];
        } catch(e) { return []; }
    }

    function saveAIHistory() {
        var rows = aiMessages.querySelectorAll('.ai-msg-row');
        var history = [];
        rows.forEach(function(row) {
            var role = row.classList.contains('user') ? 'user' : 'bot';
            var contentEl = row.querySelector('.ai-formatted');
            var timeEl = row.querySelector('.ai-msg-time');
            if (contentEl) {
                history.push({
                    role: role,
                    html: contentEl.innerHTML,
                    time: timeEl ? timeEl.textContent : ''
                });
            }
        });
        sessionStorage.setItem(WIDGET_STORAGE_KEY, JSON.stringify(history));
    }

    function loadAIHistory() {
        var history = getAIHistory();
        if (history.length === 0) return;

        history.forEach(function(msg) {
            var row = document.createElement('div');
            row.className = 'ai-msg-row ' + msg.role;
            var avatarIcon = msg.role === 'user' ? 'bx-user' : 'bx-bot';

            row.innerHTML =
                '<div class="ai-msg-avatar"><i class="bx ' + avatarIcon + '"></i></div>' +
                '<div class="ai-msg-body">' +
                    '<div class="ai-msg-bubble"><div class="ai-formatted">' + msg.html + '</div></div>' +
                    '<div class="ai-msg-time">' + msg.time + '</div>' +
                '</div>';
            row.style.animation = 'none';
            aiMessages.appendChild(row);
        });
        scrollAIMessages();
    }

    /* ── Auto-resize textarea ── */
    aiInput.addEventListener('input', function() {
        aiInput.style.height = 'auto';
        aiInput.style.height = Math.min(aiInput.scrollHeight, 90) + 'px';
    });

    /* ── Restore panel state from previous page ── */
    if (sessionStorage.getItem(WIDGET_OPEN_KEY) === '1') {
        openAIPanel();
    }
})();
</script>
