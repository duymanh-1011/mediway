// Refresh the authenticated patient's view after a cashier confirms a payment.
// GET only: the payment service remains the sole authority for collection.
const page = document.querySelector('[data-payment-snapshot]');
if (page) {
    let pending = false;
    async function refreshPayments() {
        if (pending || document.hidden) return;
        pending = true;
        try {
            const response = await fetch(page.dataset.paymentRefreshUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok || response.redirected) return;
            const state = await response.json();
            if (typeof state.snapshot === 'string' && state.snapshot !== page.dataset.paymentSnapshot) {
                window.location.reload();
            }
        } catch {
            // Keep the current view on connection errors; never infer payment success.
        } finally {
            pending = false;
        }
    }
    window.addEventListener('pageshow', refreshPayments);
    window.addEventListener('focus', refreshPayments);
    document.addEventListener('visibilitychange', refreshPayments);
    window.setInterval(refreshPayments, 15000);
}
