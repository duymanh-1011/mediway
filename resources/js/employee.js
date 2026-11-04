const menu = document.querySelector('.employee-menu');
if (menu) {
    const wide = window.matchMedia('(min-width: 901px)');
    menu.open = wide.matches;
    wide.addEventListener('change', (event) => { menu.open = event.matches; });
}

const receptionForm = document.querySelector('[data-reception-form]');
const receptionButton = receptionForm?.querySelector('[data-reception-submit]');
receptionForm?.addEventListener('submit', () => {
    if (!receptionButton) return;
    receptionButton.disabled = true;
    receptionButton.textContent = 'Đang tiếp nhận…';
});
window.addEventListener('pageshow', () => {
    if (!receptionButton) return;
    receptionButton.disabled = false;
    receptionButton.textContent = 'Xác nhận tiếp nhận';
});
