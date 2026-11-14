(() => {
    const roomFilter = document.getElementById('doctor-room-filter');
    if (roomFilter) {
        const syncRoomFilters = () => {
            const params = new URLSearchParams(window.location.search);
            const search = roomFilter.elements.namedItem('q');
            const specialty = roomFilter.elements.namedItem('specialty');

            if (search) {
                search.value = params.get('q') ?? '';
            }

            if (specialty) {
                const value = params.get('specialty') ?? '';
                specialty.value = Array.from(specialty.options).some(option => option.value === value) ? value : '';
            }
        };

        syncRoomFilters();
        window.addEventListener('pageshow', syncRoomFilters);
        window.addEventListener('popstate', syncRoomFilters);
    }

    const queue = document.querySelector('.d01-queue');
    if (!queue) {
        return;
    }

    queue.addEventListener('click', event => {
        const trigger = event.target.closest('[data-d01-dialog]');
        if (trigger) {
            event.preventDefault();
            const dialog = document.getElementById(trigger.dataset.d01Dialog);
            if (dialog && !dialog.open) {
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

    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            // The shared Portal script reloads pages restored from the back/forward cache.
            return;
        }

        const form = queue.querySelector('[data-queue-filter]');
        const params = new URLSearchParams(window.location.search);
        for (const name of ['date', 'q', 'status', 'shift']) {
            const field = form.elements.namedItem(name);
            field.value = params.get(name) ?? field.dataset.defaultValue ?? '';
        }
    });
})();
