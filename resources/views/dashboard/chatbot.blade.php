<!-- ================================================
     CHATBOT PARTIAL
     Two tabs share one draggable panel: Chat (existing widget, markup
     and IDs unchanged so chatbot.js keeps working untouched) and
     Calculator (new — wired up by math-panel.js / calculator-engine.js).
     ================================================ -->

<!-- Chat Window -->
<div id="ai-chat-window" class="chat-window-compact">

    <!-- Header (drag handle) -->
    <div class="chat-header" id="chat-drag-handle">
        <span class="chat-drag-grip" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
        </span>
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
        <div class="chat-header-actions">
            <button type="button" id="chat-reset-position" title="Reset panel position" aria-label="Reset panel position">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="7" x2="17" y2="17"></line><polyline points="17 7 17 17 7 17"></polyline></svg>
            </button>
            <button id="close-chat" aria-label="Close panel">&times;</button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="chat-tablist" role="tablist" aria-label="Math AI Assistant panel">
        <button type="button" class="chat-tab active" id="tab-chat" role="tab"
                aria-selected="true" aria-controls="panel-chat" tabindex="0">
            <svg class="chat-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            Chat
        </button>
        <button type="button" class="chat-tab" id="tab-calculator" role="tab"
                aria-selected="false" aria-controls="panel-calculator" tabindex="-1">
            <svg class="chat-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="8" y1="11" x2="8.01" y2="11"></line><line x1="12" y1="11" x2="12.01" y2="11"></line><line x1="16" y1="11" x2="16.01" y2="11"></line><line x1="8" y1="15" x2="8.01" y2="15"></line><line x1="12" y1="15" x2="12.01" y2="15"></line><line x1="16" y1="15" x2="16.01" y2="15"></line><line x1="8" y1="19" x2="8.01" y2="19"></line><line x1="12" y1="19" x2="12.01" y2="19"></line></svg>
            Calculator
        </button>
    </div>

    <!-- Chat tab panel (existing widget — unchanged) -->
    <div id="panel-chat" class="chat-tabpanel" role="tabpanel" aria-labelledby="tab-chat" tabindex="0">

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

    <!-- Calculator tab panel (new) -->
    <div id="panel-calculator" class="chat-tabpanel" role="tabpanel" aria-labelledby="tab-calculator" tabindex="0" hidden>

        <div class="calc-pane">

            <div class="calc-screen">
                <div class="calc-expression" id="calc-expression"></div>
                <div class="calc-output" id="calc-preview" aria-live="polite">0</div>
                <div class="calc-error" id="calc-error" role="alert"></div>
            </div>

            <div class="calc-keypad" id="calc-keypad">
                <button type="button" class="calc-key calc-key-fn calc-key-accent" id="calc-angle-btn" data-action="angle" aria-pressed="true">deg</button>
                <button type="button" class="calc-key calc-key-fn calc-key-accent active" id="calc-format-btn" data-action="frac" title="Toggle fraction/decimal display" aria-pressed="true">f&harr;d</button>
                <button type="button" class="calc-key calc-key-fn" data-action="backspace" aria-label="Backspace">&larr;</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="(">(</button>
                <button type="button" class="calc-key calc-key-fn" data-insert=")">)</button>

                <button type="button" class="calc-key calc-key-fn" data-action="clear">C</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="sin(">sin</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="cos(">cos</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="tan(">tan</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="/">&divide;</button>

                <button type="button" class="calc-key calc-key-fn calc-key-accent" data-insert="/" title="Fraction (a/b)">a/b</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="sqrt(">&radic;</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="log10(">log</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="log(">ln</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="*">&times;</button>

                <button type="button" class="calc-key calc-key-fn" data-insert="^">x^y</button>
                <button type="button" class="calc-key" data-insert="7">7</button>
                <button type="button" class="calc-key" data-insert="8">8</button>
                <button type="button" class="calc-key" data-insert="9">9</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="-">&minus;</button>

                <button type="button" class="calc-key calc-key-fn" data-insert="pi">&pi;</button>
                <button type="button" class="calc-key" data-insert="4">4</button>
                <button type="button" class="calc-key" data-insert="5">5</button>
                <button type="button" class="calc-key" data-insert="6">6</button>
                <button type="button" class="calc-key calc-key-fn" data-insert="+">+</button>

                <button type="button" class="calc-key calc-key-fn" data-insert="e">e</button>
                <button type="button" class="calc-key" data-insert="1">1</button>
                <button type="button" class="calc-key" data-insert="2">2</button>
                <button type="button" class="calc-key" data-insert="3">3</button>
                <button type="button" class="calc-key calc-key-equals" data-action="equals">=</button>

                <button type="button" class="calc-key calc-key-fn" data-insert="!">n!</button>
                <button type="button" class="calc-key" data-insert="0">0</button>
                <button type="button" class="calc-key" data-insert=".">.</button>
                <button type="button" class="calc-key calc-key-fn" data-action="negate">&plusmn;</button>
            </div>

            <button type="button" class="calc-ask-btn" id="calc-ask-btn" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                Ask the assistant about this
            </button>

            <div class="calc-loading" id="calc-loading" hidden>Loading calculator&hellip;</div>

        </div>

    </div>

</div>
