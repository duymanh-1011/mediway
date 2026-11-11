import { normalizeLookup } from './admin-lookups.js';

export function initializeServiceOrderPicker(root = document) {
    const picker = root.querySelector('[data-service-order-picker]');
    const searchField = picker?.querySelector('[data-service-search-field]');
    if (!searchField) return;

    const search = searchField.querySelector('input');
    const empty = picker.querySelector('[data-service-search-empty]');
    const counter = picker.querySelector('#selected-count');
    const services = Array.from(picker.querySelectorAll('.service-item'), (card) => ({
        card,
        checkbox: card.querySelector('.service-checkbox'),
        keywords: normalizeLookup(`${card.querySelector('.service-name').textContent} ${card.querySelector('.service-code').textContent}`),
    }));

    function filterServices() {
        const terms = normalizeLookup(search.value).split(/\s+/).filter(Boolean);
        let hasMatches = false;

        services.forEach(({ card, keywords }) => {
            const matches = terms.every((term) => keywords.includes(term));
            card.hidden = !matches;
            hasMatches ||= matches;
        });

        empty.hidden = hasMatches;
    }

    function updateSelectedCount() {
        counter.textContent = String(services.filter(({ checkbox }) => checkbox.checked).length);
    }

    search.addEventListener('input', filterServices);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.isComposing) event.preventDefault();
    });
    picker.addEventListener('change', (event) => {
        if (event.target.name === 'medical_service_ids[]') updateSelectedCount();
    });
    searchField.hidden = false;
    filterServices();
    updateSelectedCount();
}
