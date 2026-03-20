let timer = null;

self.onmessage = function(e) {
    if (e.data.command === 'start') {
        const interval = e.data.interval || 5000;
        if (timer) clearInterval(timer);
        timer = setInterval(() => {
            self.postMessage({ type: 'tick' });
        }, interval);
    }
    if (e.data.command === 'stop') {
        if (timer) { clearInterval(timer); timer = null; }
    }
};