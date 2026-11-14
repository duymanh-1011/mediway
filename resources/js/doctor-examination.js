document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-d01-dialog]');
    if (trigger) {
        const dialog = document.getElementById(trigger.dataset.d01Dialog);
        if (dialog instanceof HTMLDialogElement && !dialog.open) {
            dialog.showModal();
        }
    }

    if (event.target.matches('.d01-dialog')) {
        const bounds = event.target.getBoundingClientRect();
        const outside = event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom;
        if (outside) {
            event.target.close();
        }
    }
});
