import QRCode from 'qrcode';

const canvas = document.querySelector('[data-qr-payload]');
if (canvas) {
    QRCode.toCanvas(canvas, canvas.dataset.qrPayload, { width: 272, margin: 4, errorCorrectionLevel: 'M' })
        .catch(() => {
            canvas.hidden = true;
            document.querySelector('[data-qr-render-error]').hidden = false;
        });
}

const statusPanel = document.querySelector('[data-qr-status-url]');
if (statusPanel) {
    const expireAfter = Number(statusPanel.dataset.qrExpires) * 1000 - Date.now();
    if (canvas && expireAfter > 0) {
        window.setTimeout(() => { canvas.hidden = true; }, expireAfter);
    }

    async function updateStatus() {
        if (!document.hidden) {
            try {
                const response = await fetch(statusPanel.dataset.qrStatusUrl, {
                    headers: { Accept: 'application/json' }, cache: 'no-store',
                });
                if (response.status === 401 || response.status === 403) return;
                if (response.ok) {
                    const snapshot = await response.json();
                    if (snapshot.status !== statusPanel.dataset.qrStatus
                        || snapshot.expired !== (statusPanel.dataset.qrExpired === '1')
                        || snapshot.review !== (statusPanel.dataset.qrReview === '1')) {
                        window.location.reload();
                        return;
                    }
                }
            } catch {
                // Try again after a temporary network interruption.
            }
        }
        window.setTimeout(updateStatus, 4000);
    }

    window.setTimeout(updateStatus, 2500);
}
