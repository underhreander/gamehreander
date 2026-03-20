<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support — Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* ===== Support Chat Layout ===== */
        .support-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 0;
            height: calc(100vh - 120px);
            background: var(--admin-card);
            border-radius: var(--admin-radius);
            overflow: hidden;
            border: 1px solid var(--admin-border);
        }

        /* --- Sessions Sidebar --- */
        .sessions-panel {
            border-right: 1px solid var(--admin-border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .sessions-header {
            padding: 20px;
            border-bottom: 1px solid var(--admin-border);
        }
        .sessions-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--admin-text);
            margin-bottom: 12px;
        }
        .sessions-tabs {
            display: flex;
            gap: 6px;
        }
        .sessions-tab {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--admin-border);
            background: rgba(255,255,255,0.03);
            color: var(--admin-text-secondary);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-family: var(--admin-font);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .sessions-tab:hover {
            background: rgba(37,99,235,0.1);
            border-color: rgba(37,99,235,0.3);
        }
        .sessions-tab.active {
            background: rgba(37,99,235,0.15);
            border-color: var(--admin-primary);
            color: var(--admin-text);
        }
        .sessions-tab .tab-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            background: var(--admin-primary);
            color: #fff;
            border-radius: 9px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-left: 4px;
        }
        .sessions-tab .tab-badge.purchase-badge {
            background: var(--admin-warning);
        }

        .sessions-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        /* --- Session Item --- */
        .session-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 4px;
            border: 1px solid transparent;
        }
        .session-item:hover {
            background: rgba(255,255,255,0.04);
        }
        .session-item.active {
            background: rgba(37,99,235,0.1);
            border-color: rgba(37,99,235,0.2);
        }
        .session-item.unread {
            background: rgba(37,99,235,0.05);
        }
        .session-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .session-avatar.support-avatar {
            background: rgba(37,99,235,0.15);
            color: var(--admin-primary);
        }
        .session-avatar.purchase-avatar {
            background: rgba(245,158,11,0.15);
            color: var(--admin-warning);
        }
        .session-info {
            flex: 1;
            min-width: 0;
        }
        .session-info .session-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .session-ip {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--admin-text);
        }
        .session-time {
            font-size: 0.7rem;
            color: var(--admin-text-muted);
        }
        .session-preview {
            font-size: 0.8rem;
            color: var(--admin-text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .session-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }
        .session-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-open { background: rgba(34,197,94,0.15); color: var(--admin-success); }
        .badge-waiting { background: rgba(245,158,11,0.15); color: var(--admin-warning); }
        .badge-answered { background: rgba(37,99,235,0.15); color: var(--admin-primary); }
        .badge-closed { background: rgba(100,116,139,0.15); color: var(--admin-text-muted); }
        .badge-purchase { background: rgba(245,158,11,0.15); color: var(--admin-warning); }
        .badge-support { background: rgba(37,99,235,0.15); color: var(--admin-primary); }
        .unread-dot {
            width: 10px;
            height: 10px;
            background: var(--admin-primary);
            border-radius: 50%;
            flex-shrink: 0;
        }

        /* --- Chat Panel --- */
        .chat-panel {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-panel-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: var(--admin-text-secondary);
            font-size: 1rem;
            text-align: center;
            padding: 40px;
        }
        .chat-panel-empty i {
            font-size: 3rem;
            display: block;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .chat-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--admin-border);
            background: rgba(255,255,255,0.02);
        }
        .chat-top-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .chat-top-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--admin-text);
        }
        .chat-top-info span {
            font-size: 0.8rem;
            color: var(--admin-text-secondary);
        }
        .chat-top-actions {
            display: flex;
            gap: 8px;
        }
        .chat-top-btn {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid var(--admin-border);
            background: rgba(255,255,255,0.03);
            color: var(--admin-text-secondary);
            cursor: pointer;
            font-size: 0.8rem;
            font-family: var(--admin-font);
            transition: all 0.2s ease;
        }
        .chat-top-btn:hover {
            background: rgba(37,99,235,0.1);
            border-color: rgba(37,99,235,0.3);
            color: var(--admin-text);
        }
        .chat-top-btn.btn-close-session:hover {
            background: rgba(239,68,68,0.1);
            border-color: rgba(239,68,68,0.3);
            color: var(--admin-danger);
        }

        /* --- Messages --- */
        .chat-messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .admin-chat-msg {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 0.9rem;
            line-height: 1.5;
            word-break: break-word;
        }
        .admin-chat-msg.user-msg {
            align-self: flex-start;
            background: rgba(255,255,255,0.06);
            color: var(--admin-text);
            border-bottom-left-radius: 4px;
        }
        .admin-chat-msg.admin-msg {
            align-self: flex-end;
            background: var(--admin-primary);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .admin-chat-msg .msg-time {
            font-size: 0.7rem;
            opacity: 0.6;
            margin-top: 6px;
            display: block;
        }
        .admin-chat-msg.purchase-msg {
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.2);
            color: var(--admin-text);
            max-width: 80%;
        }
        .admin-chat-msg.purchase-msg .purchase-tag {
            display: inline-block;
            background: rgba(245,158,11,0.2);
            color: var(--admin-warning);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* --- Reply Bar --- */
        .chat-reply-bar {
            display: flex;
            gap: 10px;
            padding: 16px 20px;
            border-top: 1px solid var(--admin-border);
            background: rgba(255,255,255,0.02);
        }
        .chat-reply-input {
            flex: 1;
            padding: 12px 16px;
            background: var(--admin-bg);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            color: var(--admin-text);
            font-family: var(--admin-font);
            font-size: 0.9rem;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            outline: none;
            transition: border-color 0.2s ease;
        }
        .chat-reply-input:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .chat-reply-input::placeholder {
            color: var(--admin-text-muted);
        }
        .chat-reply-send {
            padding: 12px 20px;
            background: var(--admin-primary);
            color: #fff;
            border: none;
            border-radius: var(--admin-radius);
            cursor: pointer;
            font-size: 0.9rem;
            font-family: var(--admin-font);
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .chat-reply-send:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }

        /* --- User Info Modal --- */
        .user-info-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .user-info-overlay.active {
            display: flex;
        }
        .user-info-modal {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: var(--admin-radius);
            padding: 30px;
            max-width: 480px;
            width: 90%;
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .user-info-modal h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--admin-text);
            margin-bottom: 20px;
        }
        .user-info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--admin-border);
            font-size: 0.9rem;
        }
        .user-info-row:last-child { border-bottom: none; }
        .user-info-label {
            color: var(--admin-text-secondary);
            font-weight: 500;
        }
        .user-info-value {
            color: var(--admin-text);
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-break: break-all;
        }
        .user-info-close {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            color: var(--admin-text-secondary);
            cursor: pointer;
            font-family: var(--admin-font);
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .user-info-close:hover {
            background: rgba(37,99,235,0.1);
            border-color: rgba(37,99,235,0.3);
            color: var(--admin-text);
        }

        /* --- Sound Toggle --- */
        .sound-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            color: var(--admin-text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s ease;
            z-index: 100;
        }
        .sound-toggle:hover {
            background: rgba(37,99,235,0.1);
            border-color: rgba(37,99,235,0.3);
            color: var(--admin-text);
        }
        .sound-toggle.muted {
            color: var(--admin-danger);
        }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .support-layout {
                grid-template-columns: 1fr;
                height: auto;
                min-height: calc(100vh - 120px);
            }
            .sessions-panel {
                max-height: 300px;
                border-right: none;
                border-bottom: 1px solid var(--admin-border);
            }
            .chat-messages-area {
                min-height: 400px;
            }
            .admin-chat-msg {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <i class="fas fa-cube"></i>
                <span><b>Soft</b>Master</span>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="control.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="games.php"><i class="fas fa-gamepad"></i> Software</a></li>
                    <li class="active"><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Support Chat</h1>
                <div class="admin-user">
                    <i class="fas fa-user-circle"></i>
                    <span>Admin</span>
                </div>
            </div>

            <div class="support-layout">
                <!-- Sessions Panel -->
                <div class="sessions-panel">
                    <div class="sessions-header">
                        <h3>Conversations</h3>
                        <div class="sessions-tabs">
                            <button class="sessions-tab active" data-filter="all">
                                All <span class="tab-badge" id="badgeAll">0</span>
                            </button>
                            <button class="sessions-tab" data-filter="support">
                                Support <span class="tab-badge" id="badgeSupport">0</span>
                            </button>
                            <button class="sessions-tab" data-filter="purchase">
                                Purchase <span class="tab-badge purchase-badge" id="badgePurchase">0</span>
                            </button>
                        </div>
                    </div>
                    <div class="sessions-list" id="sessionsList">
                        <!-- Filled by JS -->
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel" id="chatPanel">
                    <div class="chat-panel-empty" id="chatEmpty">
                        <div>
                            <i class="fas fa-comments"></i>
                            <p>Select a conversation to start chatting</p>
                        </div>
                    </div>

                    <div id="chatActive" style="display:none;flex-direction:column;flex:1;overflow:hidden;">
                        <div class="chat-top-bar">
                            <div class="chat-top-info">
                                <div>
                                    <h4 id="chatTopIp">—</h4>
                                    <span id="chatTopCountry">—</span>
                                </div>
                            </div>
                            <div class="chat-top-actions">
                                <button class="chat-top-btn" id="btnUserInfo" title="User Info">
                                    <i class="fas fa-user"></i> Info
                                </button>
                                <button class="chat-top-btn btn-close-session" id="btnCloseSession" title="Close Session">
                                    <i class="fas fa-times-circle"></i> Close
                                </button>
                            </div>
                        </div>

                        <div class="chat-messages-area" id="adminChatMessages">
                            <!-- Filled by JS -->
                        </div>

                        <div class="chat-reply-bar">
                            <textarea class="chat-reply-input" id="adminReplyInput" placeholder="Type your reply..." rows="1"></textarea>
                            <button class="chat-reply-send" id="adminReplySend">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- User Info Modal -->
    <div class="user-info-overlay" id="userInfoOverlay">
        <div class="user-info-modal">
            <h3><i class="fas fa-user-circle"></i> User Information</h3>
            <div id="userInfoContent"></div>
            <button class="user-info-close" id="userInfoClose">Close</button>
        </div>
    </div>

    <!-- Sound Toggle -->
    <button class="sound-toggle" id="soundToggle" title="Toggle notification sounds">
        <i class="fas fa-volume-up"></i>
    </button>

    <script>
    (function() {
        'use strict';

        const API = 'api/chat_admin.php';
        const POLL = 3000;
        const SOUND_SUPPORT = '../sounds/notification-support.mp3';
        const SOUND_PURCHASE = '../sounds/notification-purchase.mp3';

        let sessions = [];
        let activeSessionId = null;
        let lastMsgId = 0;
        let pollTimer = null;
        let sessionsPollTimer = null;
        let currentFilter = 'all';
        let soundEnabled = true;
        let knownSessionIds = new Set();
        let initialLoad = true;

        // Sounds
        let supportSound = null;
        let purchaseSound = null;
        try {
            supportSound = new Audio(SOUND_SUPPORT);
            supportSound.volume = 0.6;
            purchaseSound = new Audio(SOUND_PURCHASE);
            purchaseSound.volume = 0.7;
        } catch(e) {}

        function playSound(type) {
            if (!soundEnabled) return;
            try {
                const s = (type === 'purchase' && purchaseSound) ? purchaseSound : supportSound;
                if (s) { s.currentTime = 0; s.play().catch(() => {}); }
            } catch(e) {}
        }

        // Sound toggle
        const soundToggle = document.getElementById('soundToggle');
        if (soundToggle) {
            const saved = localStorage.getItem('admin_sound');
            if (saved === 'off') {
                soundEnabled = false;
                soundToggle.classList.add('muted');
                soundToggle.querySelector('i').className = 'fas fa-volume-mute';
            }
            soundToggle.addEventListener('click', function() {
                soundEnabled = !soundEnabled;
                this.classList.toggle('muted', !soundEnabled);
                this.querySelector('i').className = soundEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';
                localStorage.setItem('admin_sound', soundEnabled ? 'on' : 'off');
            });
        }

        // Session tabs
        document.querySelectorAll('.sessions-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.sessions-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                renderSessions();
            });
        });

        // ===== LOAD SESSIONS =====
        async function loadSessions() {
            try {
                const resp = await fetch(API + '?action=get_sessions');
                const data = await resp.json();
                if (data.success) {
                    const newSessions = data.sessions || [];

                    if (!initialLoad) {
                        newSessions.forEach(s => {
                            if (!knownSessionIds.has(s.id)) {
                                playSound(s.session_type === 'purchase' ? 'purchase' : 'support');
                            }
                        });
                    }

                    sessions = newSessions;
                    knownSessionIds = new Set(sessions.map(s => s.id));
                    initialLoad = false;
                    renderSessions();
                    updateBadges();
                }
            } catch(e) {
                console.error('Load sessions error:', e);
            }
        }

        function renderSessions() {
            const list = document.getElementById('sessionsList');
            if (!list) return;

            let filtered = sessions;
            if (currentFilter === 'support') filtered = sessions.filter(s => s.session_type !== 'purchase');
            else if (currentFilter === 'purchase') filtered = sessions.filter(s => s.session_type === 'purchase');

            if (filtered.length === 0) {
                list.innerHTML = '<div style="padding:40px 20px;text-align:center;color:var(--admin-text-secondary);font-size:0.9rem;"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:12px;opacity:0.3;"></i>No conversations</div>';
                return;
            }

            list.innerHTML = filtered.map(s => {
                const isPurchase = s.session_type === 'purchase';
                const isActive = s.id == activeSessionId;
                const hasUnread = parseInt(s.unread_count || 0) > 0;
                const avatarClass = isPurchase ? 'purchase-avatar' : 'support-avatar';
                const avatarIcon = isPurchase ? 'fa-shopping-cart' : 'fa-user';
                const statusClass = 'badge-' + (s.status || 'open');
                const typeClass = isPurchase ? 'badge-purchase' : 'badge-support';
                const timeAgo = formatTime(s.last_message_at || s.created_at);

                return `
                    <div class="session-item ${isActive ? 'active' : ''} ${hasUnread ? 'unread' : ''}"
                         onclick="openSession(${s.id})" data-id="${s.id}">
                        <div class="session-avatar ${avatarClass}">
                            <i class="fas ${avatarIcon}"></i>
                        </div>
                        <div class="session-info">
                            <div class="session-top">
                                <span class="session-ip">${escapeHtml(s.ip_address || '—')}</span>
                                <span class="session-time">${timeAgo}</span>
                            </div>
                            <div class="session-preview">${escapeHtml(s.last_message || 'No messages')}</div>
                            <div class="session-meta">
                                <span class="session-badge ${statusClass}">${s.status || 'open'}</span>
                                <span class="session-badge ${typeClass}">${isPurchase ? 'purchase' : 'support'}</span>
                            </div>
                        </div>
                        ${hasUnread ? '<div class="unread-dot"></div>' : ''}
                    </div>`;
            }).join('');
        }

        function updateBadges() {
            const all = sessions.length;
            const support = sessions.filter(s => s.session_type !== 'purchase').length;
            const purchase = sessions.filter(s => s.session_type === 'purchase').length;
            const el1 = document.getElementById('badgeAll');
            const el2 = document.getElementById('badgeSupport');
            const el3 = document.getElementById('badgePurchase');
            if (el1) el1.textContent = all;
            if (el2) el2.textContent = support;
            if (el3) el3.textContent = purchase;
        }

        // ===== OPEN SESSION =====
        window.openSession = async function(sessionId) {
            activeSessionId = sessionId;
            lastMsgId = 0;

            document.getElementById('chatEmpty').style.display = 'none';
            document.getElementById('chatActive').style.display = 'flex';

            document.querySelectorAll('.session-item').forEach(el => {
                el.classList.toggle('active', el.dataset.id == sessionId);
                if (el.dataset.id == sessionId) el.classList.remove('unread');
            });

            const sess = sessions.find(s => s.id == sessionId);
            if (sess) {
                document.getElementById('chatTopIp').textContent = sess.ip_address || '—';
                document.getElementById('chatTopCountry').textContent = (sess.country || 'Unknown') + (sess.session_type === 'purchase' ? ' • Purchase Request' : '');
            }

            await loadMessages(sessionId);
            startMsgPoll();
        };

        // ===== LOAD MESSAGES =====
        async function loadMessages(sessionId) {
            try {
                const resp = await fetch(API + '?action=get_messages&session_id=' + sessionId);
                const data = await resp.json();
                const area = document.getElementById('adminChatMessages');
                area.innerHTML = '';
                if (data.success && data.messages) {
                    data.messages.forEach(msg => {
                        appendAdminMsg(msg);
                        lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
                    });
                }
                scrollChat();
            } catch(e) {
                console.error('Load messages error:', e);
            }
        }

        function appendAdminMsg(msg) {
            const area = document.getElementById('adminChatMessages');
            if (!area) return;
            const div = document.createElement('div');
            const isAdmin = msg.sender === 'admin';
            const isPurchase = msg.message && msg.message.startsWith('[PURCHASE REQUEST]');
            const time = formatMsgTime(msg.created_at);

            if (isPurchase) {
                div.className = 'admin-chat-msg user-msg purchase-msg';
                const lines = msg.message.replace('[PURCHASE REQUEST]\n', '').split('\n');
                let html = '<span class="purchase-tag"><i class="fas fa-shopping-cart"></i> Purchase Request</span><br>';
                lines.forEach(line => { if (line.trim()) html += escapeHtml(line) + '<br>'; });
                html += '<span class="msg-time">' + time + '</span>';
                div.innerHTML = html;
            } else {
                div.className = 'admin-chat-msg ' + (isAdmin ? 'admin-msg' : 'user-msg');
                div.innerHTML = escapeHtml(msg.message).replace(/\n/g, '<br>') + '<span class="msg-time">' + time + '</span>';
            }
            area.appendChild(div);
        }

        // ===== SEND REPLY =====
        const replyInput = document.getElementById('adminReplyInput');
        const replySend = document.getElementById('adminReplySend');

        if (replySend) replySend.addEventListener('click', sendReply);
        if (replyInput) {
            replyInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); }
            });
            replyInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        async function sendReply() {
            if (!activeSessionId || !replyInput) return;
            const msg = replyInput.value.trim();
            if (!msg) return;
            replyInput.value = '';
            replyInput.style.height = 'auto';

            appendAdminMsg({ sender: 'admin', message: msg, created_at: new Date().toISOString() });
            scrollChat();

            try {
                const fd = new FormData();
                fd.append('action', 'send_reply');
                fd.append('session_id', activeSessionId);
                fd.append('message', msg);
                const resp = await fetch(API, { method: 'POST', body: fd });
                const data = await resp.json();
                if (data.success && data.message_id) lastMsgId = Math.max(lastMsgId, parseInt(data.message_id));
            } catch(e) {
                console.error('Send reply error:', e);
            }
        }

        // ===== POLL NEW MESSAGES =====
        function startMsgPoll() { stopMsgPoll(); pollTimer = setInterval(pollNewMessages, POLL); }
        function stopMsgPoll() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

        async function pollNewMessages() {
            if (!activeSessionId) return;
            try {
                const resp = await fetch(API + '?action=poll_new&session_id=' + activeSessionId + '&last_id=' + lastMsgId);
                const data = await resp.json();
                if (data.success && data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        if (msg.sender === 'user') {
                            appendAdminMsg(msg);
                            const sess = sessions.find(s => s.id == activeSessionId);
                            playSound((sess && sess.session_type === 'purchase') ? 'purchase' : 'support');
                        }
                        lastMsgId = Math.max(lastMsgId, parseInt(msg.id));
                    });
                    scrollChat();
                }
            } catch(e) {}
        }

        // ===== USER INFO =====
        const btnUserInfo = document.getElementById('btnUserInfo');
        const userInfoOverlay = document.getElementById('userInfoOverlay');
        const userInfoClose = document.getElementById('userInfoClose');

        if (btnUserInfo) {
            btnUserInfo.addEventListener('click', async function() {
                if (!activeSessionId) return;
                try {
                    const resp = await fetch(API + '?action=get_user_info&session_id=' + activeSessionId);
                    const data = await resp.json();
                    if (data.success && data.info) {
                        const info = data.info;
                        document.getElementById('userInfoContent').innerHTML = `
                            <div class="user-info-row"><span class="user-info-label">IP Address</span><span class="user-info-value">${escapeHtml(info.ip_address || '—')}</span></div>
                            <div class="user-info-row"><span class="user-info-label">Country</span><span class="user-info-value">${escapeHtml(info.country || 'Unknown')}</span></div>
                            <div class="user-info-row"><span class="user-info-label">Session Type</span><span class="user-info-value">${escapeHtml(info.session_type || 'support')}</span></div>
                            <div class="user-info-row"><span class="user-info-label">Status</span><span class="user-info-value">${escapeHtml(info.status || '—')}</span></div>
                            <div class="user-info-row"><span class="user-info-label">Created</span><span class="user-info-value">${escapeHtml(info.created_at || '—')}</span></div>
                            <div class="user-info-row"><span class="user-info-label">User Agent</span><span class="user-info-value" style="font-size:0.75rem;">${escapeHtml(info.user_agent || '—')}</span></div>
                            <div class="user-info-row"><span class="user-info-label">Total Visits</span><span class="user-info-value">${info.visits ?? '—'}</span></div>
                            <div class="user-info-row"><span class="user-info-label">Total Downloads</span><span class="user-info-value">${info.downloads ?? '—'}</span></div>`;
                        userInfoOverlay.classList.add('active');
                    }
                } catch(e) {}
            });
        }
        if (userInfoClose) userInfoClose.addEventListener('click', () => userInfoOverlay.classList.remove('active'));
        if (userInfoOverlay) userInfoOverlay.addEventListener('click', (e) => { if (e.target === userInfoOverlay) userInfoOverlay.classList.remove('active'); });

        // ===== CLOSE SESSION =====
        const btnCloseSession = document.getElementById('btnCloseSession');
        if (btnCloseSession) {
            btnCloseSession.addEventListener('click', async function() {
                if (!activeSessionId || !confirm('Close this session?')) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'close_session');
                    fd.append('session_id', activeSessionId);
                    await fetch(API, { method: 'POST', body: fd });
                    activeSessionId = null;
                    document.getElementById('chatActive').style.display = 'none';
                    document.getElementById('chatEmpty').style.display = '';
                    stopMsgPoll();
                    loadSessions();
                } catch(e) {}
            });
        }

        // ===== HELPERS =====
        function scrollChat() {
            const area = document.getElementById('adminChatMessages');
            if (area) setTimeout(() => { area.scrollTop = area.scrollHeight; }, 50);
        }
        function escapeHtml(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }
        function formatTime(dateStr) {
            if (!dateStr) return '';
            const diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
            if (diff < 60) return 'now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            return Math.floor(diff / 86400) + 'd ago';
        }
        function formatMsgTime(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // ===== INIT =====
        loadSessions();
        sessionsPollTimer = setInterval(loadSessions, 5000);
    })();
    </script>
<script src="js/admin-notifications.js"></script>
</body>
</html>