/* ============================================
   SOFTMASTER — DOWNLOAD PAGE JS
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {

    const trialForm = document.getElementById('trialForm');
    const codeResult = document.getElementById('codeResult');
    const trialCode = document.getElementById('trialCode');
    const downloadBox = document.getElementById('downloadBox');

    if (trialForm) {
        trialForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const code = generateTrialCode();
            if (trialCode) trialCode.textContent = code;

            if (codeResult) codeResult.classList.remove('hidden');

            setTimeout(function () {
                if (downloadBox) {
                    downloadBox.classList.remove('hidden');
                    downloadBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                logDownload(code);
            }, 2000);
        });
    }

    function generateTrialCode() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let code = '';
        for (let i = 0; i < 16; i++) {
            if (i > 0 && i % 4 === 0) code += '-';
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    }

    function logDownload(code) {
        const emailInput = document.getElementById('userEmail');
        fetch('admin/api/log_download.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: code,
                email: emailInput ? emailInput.value : ''
            })
        })
        .then(r => r.json())
        .then(data => console.log('Download logged:', data))
        .catch(err => console.error('Error logging download:', err));
    }

});