(function() {
    'use strict';

    const API = 'api/chat_admin.php';
    const POLL_INTERVAL = 5000;
    const SOUND_SUPPORT = '../sounds/notification-support.mp3';
    const SOUND_PURCHASE = '../sounds/notification-purchase.mp3';

    // ===== Sound setup =====
    let soundEnabled = localStorage.getItem('admin_sound') !== 'off';
    let supportSound = null;
    let purchaseSound = null;

    try {
        supportSound = new Audio(SOUND_SUPPORT);
        supportSound.volume = 0.6;
        purchaseSound = new Audio(SOUND_PURCHASE);
        purchaseSound.volume = 0.7;
    } catch(e) {}

    // Unlock audio on first user interaction (browser policy)
    let audioUnlocked = false;
    function unlockAudio() {
        if (audioUnlocked) return;
        audioUnlocked = true;
        if (supportSound) supportSound.play().then(() => { supportSound.pause(); supportSound.currentTime = 0; }).catch(() => {});
        if (purchaseSound) purchaseSound.play().then(() => { purchaseSound.pause(); purchaseSound.currentTime = 0; }).catch(() => {});
    }
    document.addEventListener('click', unlockAudio, { once: true });
    document.addEventListener('keydown', unlockAudio, { once: true });

    function playSound(type) {
        if (!soundEnabled) return;
        try {
            const s = (type === 'purchase' && purchaseSound) ? purchaseSound : supportSound;
            if (s) { s.currentTime = 0; s.play().catch(() => {}); }
        } catch(e) {}
    }

    // ===== State =====
    let knownSessionIds = new Set();
    let initialLoad = true;

    // ===== Sync sound toggle across pages =====
    window.addEventListener('storage', function(e) {
        if (e.key === 'admin_sound') {
            soundEnabled = e.newValue !== 'off';
            // Update toggle button if it exists on this page
            const btn = document.getElementById('soundToggle');
            if (btn) {
                btn.classList.toggle('muted', !soundEnabled);
                const icon = btn.querySelector('i');
                if (icon) icon.className = soundEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';
            }
        }
    });

    // ===== Check for new sessions =====
    async function checkNewSessions() {
        try {
            const resp = await fetch(API + '?action=get_sessions');
            const data = await resp.json();
            if (!data.success) return;

            const sessions = data.sessions || [];

            if (!initialLoad) {
                sessions.forEach(s => {
                    if (!knownSessionIds.has(s.id)) {
                        playSound(s.session_type === 'purchase' ? 'purchase' : 'support');
                        showBrowserNotification(s);
                    }
                });
            }

            knownSessionIds = new Set(sessions.map(s => s.id));
            initialLoad = false;

            // Update page title with unread count
            const totalUnread = sessions.reduce((sum, s) => sum + (parseInt(s.unread_count) || 0), 0);
            const baseTitle = document.title.replace(/^\(\d+\)\s*/, '');
            document.title = totalUnread > 0 ? `(${totalUnread}) ${baseTitle}` : baseTitle;

        } catch(e) {}
    }

    // ===== Browser notifications =====
    function showBrowserNotification(session) {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'granted') {
            createNotification(session);
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(p => {
                if (p === 'granted') createNotification(session);
            });
        }
    }

    function createNotification(session) {
        const isPurchase = session.session_type === 'purchase';
        const n = new Notification(isPurchase ? 'New Purchase Request' : 'New Support Ticket', {
            body: (session.ip_address || 'Unknown') + ' — ' + (session.last_message || 'New message'),
            icon: isPurchase ? '../sounds/icon-purchase.png' : '../sounds/icon-support.png',
            tag: 'session-' + session.id,
            requireInteraction: true
        });
        n.onclick = function() {
            window.focus();
            window.location.href = 'support.php';
            n.close();
        };
    }

    // ===== Web Worker for reliable background polling =====
    let worker = null;
    if (window.Worker) {
        try {
            worker = new Worker('js/notification-worker.js');
            worker.onmessage = function(e) {
                if (e.data.type === 'tick') {
                    checkNewSessions();
                }
            };
            worker.postMessage({ command: 'start', interval: POLL_INTERVAL });
        } catch(e) {
            // Fallback to setInterval
            setInterval(checkNewSessions, POLL_INTERVAL);
        }
    } else {
        setInterval(checkNewSessions, POLL_INTERVAL);
    }

    // Initial check
    checkNewSessions();

    // Expose for sound toggle buttons on any page
    window.adminNotifications = {
        toggleSound: function() {
            soundEnabled = !soundEnabled;
            localStorage.setItem('admin_sound', soundEnabled ? 'on' : 'off');
            return soundEnabled;
        },
        isSoundEnabled: function() { return soundEnabled; }
    };

})();