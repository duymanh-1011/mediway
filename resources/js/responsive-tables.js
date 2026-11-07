export function initializeResponsiveTables(selector) {
    document.querySelectorAll(selector).forEach((table) => {
        if (table.hasAttribute('data-mobile-records') && table.tHead?.rows.length === 1) {
            const headers = [...table.tHead.rows[0].cells];
            const rows = [...table.tBodies].flatMap((body) => [...body.rows]);
            const canStack = headers.every((header) => header.colSpan === 1) && rows.every((row) =>
                row.cells.length === 1 || (row.cells.length === headers.length
                    && [...row.cells].every((cell) => cell.colSpan === 1 && cell.rowSpan === 1)));
            if (canStack) {
                table.setAttribute('role', 'table');
                table.querySelectorAll('thead, tbody').forEach((group) => group.setAttribute('role', 'rowgroup'));
                table.querySelectorAll('tr').forEach((row) => row.setAttribute('role', 'row'));
                headers.forEach((header) => header.setAttribute('role', 'columnheader'));
                rows.forEach((row) => [...row.cells].forEach((cell, index) => {
                    cell.setAttribute('role', 'cell');
                    if (row.cells.length === 1) return;
                    const label = document.createElement('span');
                    label.className = 'record-cell-label';
                    label.setAttribute('aria-hidden', 'true');
                    label.textContent = headers[index].textContent.trim();
                    cell.prepend(label);
                }));
                table.setAttribute('data-mobile-ready', '');
            }
        }

        const region = table.parentElement;
        if (!['auto', 'scroll'].includes(getComputedStyle(region).overflowX)) return;
        const hint = document.createElement('p');
        hint.className = 'record-scroll-hint';
        hint.textContent = 'Cuộn ngang để xem thêm cột và thao tác.';
        hint.hidden = true;
        region.before(hint);

        const updateScrollHint = () => {
            const scrolls = region.scrollWidth > region.clientWidth + 1;
            hint.hidden = !scrolls;
            if (scrolls) {
                region.tabIndex = 0;
                region.setAttribute('role', 'region');
                region.setAttribute('aria-label', region.dataset.scrollLabel || 'Bảng dữ liệu, có thể cuộn ngang');
            } else {
                region.removeAttribute('tabindex');
                region.removeAttribute('role');
                region.removeAttribute('aria-label');
            }
        };
        updateScrollHint();
        const observer = new ResizeObserver(updateScrollHint);
        observer.observe(region);
        observer.observe(table);
    });
}
