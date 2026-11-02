(() => {
    const form = document.querySelector('[data-profile-form]');
    const button = form?.querySelector('[data-profile-submit]');
    const status = form?.querySelector('[data-profile-status]');
    if (!form || !button || !status) return;
    const reset = () => { button.disabled = false; button.textContent = 'Lưu thay đổi'; status.textContent = ''; };
    window.addEventListener('pageshow', reset);
    form.addEventListener('submit', () => {
        if (button.disabled) return;
        button.disabled = true;
        button.textContent = 'Đang lưu…';
        status.textContent = 'Đang lưu thay đổi, vui lòng chờ.';
    });
})();
