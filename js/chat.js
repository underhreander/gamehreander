(function() {
    'use strict';

    // ===== CONFIG =====
    const API_URL = 'admin/api/chat.php';
    const POLL_INTERVAL = 3000;
    const BACKGROUND_POLL_INTERVAL = 10000;
    const SOUND_USER = 'sounds/notification-user.mp3';

    // ===== SESSION =====
    let sessionHash = localStorage.getItem('chat_session_hash');
    if (!sessionHash) {
        sessionHash = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
        localStorage.setItem('chat_session_hash', sessionHash);
    }

    // ===== STATE =====
    let lastMessageId = 0;
    let chatOpen = false;
    let pollTimer = null;
    let bgPollTimer = null;
    let soundEnabled = true;

    // ===== SOUND =====
    let userSound = null;
    try {
        userSound = new Audio(SOUND_USER);
        userSound.volume = 0.5;
    } catch (e) {}

    function playUserSound() {
        if (!soundEnabled || !userSound) return;
        try {
            userSound.currentTime = 0;
            userSound.play().catch(() => {});
        } catch (e) {}
    }

    // ===== DOM ELEMENTS =====
    const chatWidget = document.getElementById('chatWidget');
    const chatToggle = document.getElementById('chatToggle');
    const chatClose = document.getElementById('chatClose');
    const chatWindow = document.getElementById('chatWindow');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatBadge = document.getElementById('chatBadge');

    if (!chatWidget || !chatToggle || !chatMessages || !chatInput || !chatSend) return;

    // ===== TOGGLE CHAT =====
    chatToggle.addEventListener('click', function() {
        chatOpen = !chatOpen;
        chatWidget.classList.toggle('open', chatOpen);

        if (chatOpen) {
            markRead();
            updateBadge(0);
            scrollToBottom();
            chatInput.focus();
            startPoll();
            stopBgPoll();
        } else {
            stopPoll();
            startBgPoll();
        }
    });

    if (chatClose) {
        chatClose.addEventListener('click', function() {
            chatOpen = false;
            chatWidget.classList.remove('open');
            stopPoll();
            startBgPoll();
        });
    }

    // ===== SEND MESSAGE =====
    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize textarea
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        // Add message to UI immediately
        appendMessage('user', message);
        chatInput.value = '';
        chatInput.style.height = 'auto';

        try {
            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('session_hash', sessionHash);
            formData.append('message', message);

            const resp = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success && data.message_id) {
                lastMessageId = Math.max(lastMessageId, data.message_id);
            }
        } catch (err) {
            console.error('Chat send error:', err);
        }
    }

    // ===== APPEND MESSAGE =====
    function appendMessage(sender, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message ' + sender;

        const contentDiv = document.createElement('div');
        contentDiv.className = 'chat-message-content';

        const p = document.createElement('p');
        p.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');

        contentDiv.appendChild(p);
        msgDiv.appendChild(contentDiv);
        chatMessages.appendChild(msgDiv);
        scrollToBottom();
    }

    // ===== POLLING =====
    function startPoll() {
        stopPoll();
        pollTimer = setInterval(pollMessages, POLL_INTERVAL);
    }

    function stopPoll() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function startBgPoll() {
        stopBgPoll();
        bgPollTimer = setInterval(checkUnread, BACKGROUND_POLL_INTERVAL);
    }

    function stopBgPoll() {
        if (bgPollTimer) {
            clearInterval(bgPollTimer);
            bgPollTimer = null;
        }
    }

    async function pollMessages() {
        try {
            const formData = new FormData();
            formData.append('action', 'poll');
            formData.append('session_hash', sessionHash);
            formData.append('last_id', lastMessageId);

            const resp = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    appendMessage('admin', msg.message);
                    lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                });
                playUserSound();
                if (chatOpen) markRead();
            }
        } catch (err) {
            console.error('Poll error:', err);
        }
    }

    async function checkUnread() {
        try {
            const formData = new FormData();
            formData.append('action', 'check_unread');
            formData.append('session_hash', sessionHash);

            const resp = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success) {
                const count = parseInt(data.unread) || 0;
                updateBadge(count);
                if (count > 0) {
                    playUserSound();
                }
            }
        } catch (err) {}
    }

    async function markRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('session_hash', sessionHash);

            await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
        } catch (err) {}
    }

    // ===== LOAD HISTORY =====
    async function loadHistory() {
        try {
            const formData = new FormData();
            formData.append('action', 'history');
            formData.append('session_hash', sessionHash);

            const resp = await fetch(API_URL, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    appendMessage(msg.sender, msg.message);
                    lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                });
            }
        } catch (err) {
            console.error('History load error:', err);
        }
    }

    // ===== UI HELPERS =====
    function updateBadge(count) {
        if (!chatBadge) return;
        if (count > 0) {
            chatBadge.textContent = count > 99 ? '99+' : count;
            chatBadge.style.display = 'flex';
        } else {
            chatBadge.style.display = 'none';
        }
    }

    function scrollToBottom() {
        if (chatMessages) {
            setTimeout(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 50);
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ===== INIT =====
    loadHistory();
    startBgPoll();
    checkUnread();

})();