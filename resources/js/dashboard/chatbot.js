import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function () {

    const chatWindow = document.getElementById('ai-chat-window');
    const chatContent = document.getElementById('chat-content');
    const input = document.getElementById('ai-input');
    const sendBtn = document.getElementById('ai-send-btn');
    const closeChatBtn = document.getElementById('close-chat');
    const sidebarChatBtn = document.getElementById('sidebar-chat-btn');
    const fabChatBtn = document.getElementById('fab-chat');
    const startChatBtn = document.getElementById('start-chat-btn');

    let isOpen = false;
    let isSending = false;

    // ============================================
    // GET CSRF TOKEN
    // ============================================

    function getCsrfToken() {

        return (
            window.Laravel?.csrfToken ||
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ||
            ''
        );

    }

    // ============================================
    // GET CURRENT TIME
    // ============================================

    function getTime() {

        return new Date().toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

    }

    // ============================================
    // OPEN CHAT
    // ============================================

    window.openChat = function () {

        isOpen = true;

        if (chatWindow) {
            chatWindow.classList.add('open');
        }

        setTimeout(() => {

            if (input) {
                input.focus();
            }

        }, 300);

    };

    // ============================================
    // CLOSE CHAT
    // ============================================

    window.closeChat = function () {

        isOpen = false;

        chatWindow?.classList.remove('open');

    };

    closeChatBtn?.addEventListener('click', window.closeChat);

    // ============================================
    // SHOW TYPING INDICATOR
    // ============================================

    function showTyping() {

        if (!chatContent) {
            return;
        }

        // Prevent duplicate typing indicators
        removeTyping();

        const typing = document.createElement('div');

        typing.id = 'typing-indicator';
        typing.className = 'msg bot';

        typing.innerHTML = `
            <div class="msg-bubble typing-indicator">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;

        chatContent.appendChild(typing);

        scrollBottom();

    }

    // ============================================
    // REMOVE TYPING INDICATOR
    // ============================================

    function removeTyping() {

        const typing = document.getElementById(
            'typing-indicator'
        );

        typing?.remove();

    }

    // ============================================
    // AUTO SCROLL
    // ============================================

    function scrollBottom() {

        if (!chatContent) {
            return;
        }

        chatContent.scrollTop = chatContent.scrollHeight;

    }

    // ============================================
    // NOTIFICATION HELPERS
    // ============================================

    function showSuccess(message) {

        if (window.Notification?.success) {

            Notification.success(message);

            return;

        }

        console.log(message);

    }

    function showError(message) {

        if (window.Notification?.error) {

            Notification.error(message);

            return;

        }

        console.error(message);

    }

    function showWarning(message) {

        if (window.Notification?.warning) {

            Notification.warning(message);

            return;

        }

        console.warn(message);

    }

    // ============================================
    // APPEND MESSAGE
    // ============================================

    function appendMessage(text, type) {

        if (!chatContent) {
            return;
        }

        const msg = document.createElement('div');

        msg.className = `msg ${type}`;

        // ========================================
        // MESSAGE BUBBLE
        // ========================================

        const bubble = document.createElement('div');

        bubble.className = 'msg-bubble';

        // ========================================
        // USER MESSAGE
        // ========================================

        if (type === 'user') {

            bubble.innerText = text;

        }

        // ========================================
        // BOT MESSAGE
        // ========================================

        else {

            let formatted = String(text ?? '');

            // Escape dangerous HTML first
            formatted = formatted
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Bold markdown
            formatted = formatted.replace(
                /\*\*(.*?)\*\*/g,
                '<strong>$1</strong>'
            );

            // Italic markdown
            formatted = formatted.replace(
                /\*(.*?)\*/g,
                '<em>$1</em>'
            );

            // Preserve line breaks
            formatted = formatted.replace(
                /\n/g,
                '<br>'
            );

            bubble.innerHTML = formatted;

        }

        // ========================================
        // TIME
        // ========================================

        const time = document.createElement('span');

        time.className = 'msg-time';

        time.textContent = getTime();

        // ========================================
        // APPEND
        // ========================================

        msg.appendChild(bubble);
        msg.appendChild(time);

        chatContent.appendChild(msg);

        scrollBottom();

        // ========================================
        // RENDER LATEX
        // ========================================

        if (
            window.MathJax &&
            type === 'bot'
        ) {

            try {

                MathJax.typesetClear([bubble]);

                MathJax.typesetPromise([bubble])
                    .catch((error) => {

                        console.error(
                            'MathJax Error:',
                            error
                        );

                    });

            } catch (error) {

                console.error(
                    'MathJax rendering error:',
                    error
                );

            }

        }

    }

    // ============================================
    // GET SERVER ERROR MESSAGE
    // ============================================

    function getServerErrorMessage(
        status,
        data,
        rawResponse
    ) {

        // Laravel validation error
        if (
            data &&
            data.errors
        ) {

            const errors = Object.values(
                data.errors
            )
                .flat()
                .join(' ');

            return errors;

        }

        // Laravel message
        if (
            data &&
            data.message
        ) {

            return data.message;

        }

        // API error
        if (
            data &&
            data.error
        ) {

            if (
                typeof data.error === 'string'
            ) {

                return data.error;

            }

            if (
                data.error.message
            ) {

                return data.error.message;

            }

        }

        // HTTP status
        if (status === 419) {

            return 'Your session has expired. Please refresh the page and try again.';

        }

        if (status === 401) {

            return 'You are not authorized to use the chatbot.';

        }

        if (status === 403) {

            return 'You do not have permission to use the chatbot.';

        }

        if (status === 404) {

            return 'Chatbot endpoint was not found. Please check the Laravel route.';

        }

        if (status === 429) {

            return 'Too many requests. Please wait a moment and try again.';

        }

        if (status >= 500) {

            return 'The chatbot server encountered an error. Please try again later.';

        }

        // If server returned HTML
        if (
            rawResponse &&
            rawResponse.includes('<!DOCTYPE')
        ) {

            return 'The server returned an unexpected HTML response. Please check the Laravel logs.';

        }

        return `Request failed with HTTP status ${status}.`;

    }

    // ============================================
    // SEND MESSAGE
    // ============================================

    async function sendMessage(text = null) {

        // Prevent multiple requests
        if (isSending) {
            return;
        }

        const message = (
            text ??
            input?.value ??
            ''
        ).trim();

        // ========================================
        // EMPTY MESSAGE
        // ========================================

        if (!message) {

            showWarning(
                'Please enter a message'
            );

            return;

        }

        // ========================================
        // CHARACTER LIMIT
        // ========================================

        if (message.length > 1000) {

            showWarning(
                'Maximum 1000 characters allowed'
            );

            return;

        }

        // ========================================
        // CSRF TOKEN
        // ========================================

        const token = getCsrfToken();

        if (!token) {

            console.error(
                'CSRF token was not found.'
            );

            appendMessage(
                'Security token not found. Please refresh the page and try again.',
                'bot'
            );

            showError(
                'CSRF token not found'
            );

            return;

        }

        // ========================================
        // USER MESSAGE
        // ========================================

        appendMessage(
            message,
            'user'
        );

        // ========================================
        // CLEAR INPUT
        // ========================================

        if (
            input &&
            !text
        ) {

            input.value = '';

            input.style.height = 'auto';

        }

        // ========================================
        // LOADING
        // ========================================

        isSending = true;

        if (sendBtn) {

            sendBtn.disabled = true;

            sendBtn.classList.add(
                'loading'
            );

        }

        showTyping();

        // ========================================
        // FETCH
        // ========================================

        try {

            console.log(
                'Sending chatbot request...'
            );

            const response = await fetch(
                '/chatbot/ask',
                {

                    method: 'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            token,

                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'

                    },

                    credentials: 'same-origin',

                    body: JSON.stringify({

                        message: message

                    })

                }
            );

            // ====================================
            // GET RAW RESPONSE FIRST
            // ====================================

            const rawResponse =
                await response.text();

            console.log(
                'Chatbot HTTP Status:',
                response.status
            );

            console.log(
                'Chatbot Raw Response:',
                rawResponse
            );

            removeTyping();

            // ====================================
            // PARSE JSON
            // ====================================

            let data = null;

            if (rawResponse) {

                try {

                    data = JSON.parse(
                        rawResponse
                    );

                } catch (jsonError) {

                    console.error(
                        'JSON Parse Error:',
                        jsonError
                    );

                    console.error(
                        'Server Response:',
                        rawResponse
                    );

                    appendMessage(
                        'The chatbot server returned an invalid response. Please check the Laravel server logs.',
                        'bot'
                    );

                    showError(
                        'Invalid server response'
                    );

                    return;

                }

            }

            // ====================================
            // HTTP ERROR
            // ====================================

            if (!response.ok) {

                const errorMessage =
                    getServerErrorMessage(
                        response.status,
                        data,
                        rawResponse
                    );

                console.error(
                    'Chatbot Server Error:',
                    errorMessage
                );

                appendMessage(
                    errorMessage,
                    'bot'
                );

                showError(
                    errorMessage
                );

                return;

            }

            // ====================================
            // SUCCESS
            // ====================================

            if (
                data &&
                data.status === 'success'
            ) {

                const reply =
                    data.reply ??
                    data.message ??
                    'The AI did not return a response.';

                appendMessage(
                    reply,
                    'bot'
                );

                showSuccess(
                    'Response received!'
                );

                return;

            }

            // ====================================
            // API FAILED
            // ====================================

            const errorMessage =
                getServerErrorMessage(
                    response.status,
                    data,
                    rawResponse
                );

            console.error(
                'Chatbot API Error:',
                data
            );

            appendMessage(
                errorMessage ||
                'The AI service could not process your request.',
                'bot'
            );

            showError(
                'AI service failed'
            );

        }

        // ========================================
        // NETWORK / FETCH ERROR
        // ========================================

        catch (error) {

            removeTyping();

            console.error(
                'CHATBOT FETCH ERROR:',
                error
            );

            let errorMessage =
                'Unable to connect to the chatbot server.';

            // Actual browser/network failure
            if (
                error instanceof TypeError
            ) {

                errorMessage =
                    'Unable to connect to the chatbot server. Please check your connection or make sure the Laravel server is running.';

            }

            appendMessage(
                errorMessage,
                'bot'
            );

            showError(
                error.message ||
                'Chatbot connection failed'
            );

        }

        // ========================================
        // FINALLY
        // ========================================

        finally {

            isSending = false;

            if (sendBtn) {

                sendBtn.disabled = false;

                sendBtn.classList.remove(
                    'loading'
                );

            }

            scrollBottom();

        }

    }

    // ============================================
    // SEND BUTTON
    // ============================================

    sendBtn?.addEventListener(
        'click',
        () => {

            sendMessage();

        }
    );

    // ============================================
    // ENTER KEY
    // ============================================

    input?.addEventListener(
        'keydown',
        (event) => {

            if (
                event.key === 'Enter' &&
                !event.shiftKey
            ) {

                event.preventDefault();

                sendMessage();

            }

        }
    );

    // ============================================
    // AUTO RESIZE TEXTAREA
    // ============================================

    input?.addEventListener(
        'input',
        () => {

            input.style.height = 'auto';

            input.style.height =
                Math.min(
                    input.scrollHeight,
                    120
                ) + 'px';

        }
    );

    // ============================================
    // CHARACTER COUNT
    // ============================================

    input?.addEventListener(
        'input',
        () => {

            const maxChars = 1000;

            if (
                input.value.length >
                maxChars
            ) {

                input.value =
                    input.value.substring(
                        0,
                        maxChars
                    );

                showWarning(
                    `Maximum ${maxChars} characters allowed`
                );

            }

        }
    );

    // ============================================
    // QUICK REPLIES
    // ============================================

    chatContent?.addEventListener(
        'click',
        (event) => {

            const button =
                event.target.closest(
                    '.quick-reply-btn'
                );

            if (!button) {
                return;
            }

            const message =
                button.textContent.trim();

            if (message) {

                sendMessage(
                    message
                );

            }

        }
    );

    // ============================================
    // OPEN CHAT BUTTONS
    // ============================================

    sidebarChatBtn?.addEventListener(
        'click',
        window.openChat
    );

    fabChatBtn?.addEventListener(
        'click',
        window.openChat
    );

    startChatBtn?.addEventListener(
        'click',
        window.openChat
    );

    // ============================================
    // DEBUG
    // ============================================

    console.log(
        'AI Chatbot initialized successfully.'
    );

});