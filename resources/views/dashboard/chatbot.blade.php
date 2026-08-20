<!-- ================================================
     CHATBOT PARTIAL
     ================================================ -->

<!-- Chat Window -->
<div id="ai-chat-window" class="chat-window-compact">

    <!-- Header -->
    <div class="chat-header">
        <div class="user-info">
            <div class="chat-avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <div class="chat-info">
                <span class="chat-name">Math AI Assistant</span>
                <span class="chat-status-text">Online</span>
            </div>
        </div>
        <button id="close-chat">&times;</button>
    </div>

    <!-- Messages -->
    <div id="chat-content" class="chat-content">
        <div class="msg bot">
            <div class="msg-bubble">Hello! I'm here to help you with your math questions. Ask me about <strong>Sequences</strong>, <strong>Polynomials</strong>, or <strong>Functions</strong>.</div>
            <div class="quick-replies">
                <button class="quick-reply-btn">Sequences</button>
                <button class="quick-reply-btn">Polynomials</button>
                <button class="quick-reply-btn">Functions</button>
            </div>
            <span class="msg-time">Just now</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="chat-footer">
        <div class="symbol-row" id="ai-symbol-row">
            <button type="button" class="symbol-btn" data-symbol="√">√</button>
            <button type="button" class="symbol-btn" data-symbol="π">π</button>
            <button type="button" class="symbol-btn" data-symbol="×">×</button>
            <button type="button" class="symbol-btn" data-symbol="÷">÷</button>
            <button type="button" class="symbol-btn" data-symbol="±">±</button>
            <button type="button" class="symbol-btn" data-symbol="≤">≤</button>
            <button type="button" class="symbol-btn" data-symbol="≥">≥</button>
            <button type="button" class="symbol-btn" data-symbol="≠">≠</button>
            <button type="button" class="symbol-btn" data-symbol="∞">∞</button>
            <button type="button" class="symbol-btn" data-symbol="²">x²</button>
            <button type="button" class="symbol-btn" data-symbol="³">x³</button>
            <button type="button" class="symbol-btn" data-symbol="∑">∑</button>
        </div>
        <div class="input-row">
            <input type="text" id="ai-input" placeholder="Type your question...">
            <button id="ai-send-btn" title="Send message">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </div>
    </div>

</div>