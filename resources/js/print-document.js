document.querySelector('[data-print-document]')?.addEventListener('click', () => window.print());

const printTime = document.querySelector('[data-result-print-time]');
if (printTime) {
    window.addEventListener('beforeprint', () => {
        printTime.textContent = new Intl.DateTimeFormat('vi-VN', {
            timeZone: 'Asia/Ho_Chi_Minh', dateStyle: 'short', timeStyle: 'short',
        }).format(new Date());
    });
}
