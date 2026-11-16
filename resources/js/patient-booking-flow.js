const BOOKING_KIND_LABELS = {
    specialty: 'Chuyên khoa đã chọn',
    doctor: 'Bác sĩ đã chọn',
    room: 'Phòng khám đã chọn',
};

const SPECIALTY_SEARCH_ALIASES = {
    'da khoa': ['khám tổng quát', 'đau bụng', 'đau đầu', 'sốt', 'mệt mỏi', 'khám ban đầu'],
    'tai mui hong': ['xoang', 'viêm mũi', 'nghẹt mũi', 'chảy mũi', 'viêm họng', 'đau họng', 'amidan', 'ù tai', 'đau tai', 'nghe kém'],
    'tim mach': ['huyết áp', 'mạch máu', 'đau ngực', 'hồi hộp', 'rối loạn nhịp tim', 'khó thở'],
    'noi tong quat': ['tiểu đường', 'đái tháo đường', 'tuyến giáp', 'thiếu máu', 'mỡ máu', 'gan', 'thận', 'sốt', 'mệt mỏi'],
    'tieu hoa': ['đại tràng', 'viêm đại tràng', 'đau đại tràng', 'trực tràng', 'đường ruột', 'ruột non', 'ruột già', 'dạ dày', 'thực quản', 'gan mật', 'táo bón', 'tiêu chảy', 'ợ chua', 'trào ngược', 'đau bụng', 'đầy hơi', 'khó tiêu', 'đại tiện'],
    'da lieu': ['ngứa', 'mụn', 'dị ứng', 'phát ban', 'mề đay', 'viêm da', 'nấm da', 'tóc', 'móng'],
};

const normalizeSearch = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('vi')
    .trim();

export function bookingOptionMatches(option, query) {
    const terms = normalizeSearch(query).split(/\s+/).filter(Boolean);
    const specialtyAliases = option.kind === 'specialty'
        ? SPECIALTY_SEARCH_ALIASES[normalizeSearch(option.name)] ?? []
        : [];
    const searchableText = normalizeSearch(`${option.name ?? ''} ${option.detail ?? ''} ${option.search ?? ''} ${specialtyAliases.join(' ')}`);
    return terms.length === 0 || terms.every((term) => searchableText.includes(term));
}

export function bookingSlotMatches(slot, selection) {
    if (!selection || !['specialty', 'doctor', 'room'].includes(selection.kind)) return false;
    const idField = { specialty: 'specialtyId', doctor: 'doctorId', room: 'roomId' }[selection.kind];
    return String(slot[idField] ?? '') === String(selection.id);
}

export function bookingChangeKind(selection) {
    return ['specialty', 'doctor', 'room'].includes(selection?.kind) ? selection.kind : null;
}

export function initializePatientBookingFlow(root = document) {
    const flow = root.querySelector('[data-booking-flow]:not([data-booking-ready])');
    if (!flow) return;
    flow.dataset.bookingReady = 'true';

    const selectionStep = flow.querySelector('[data-booking-selection-step]');
    const scheduleStep = root.querySelector('[data-booking-schedule-step]');
    const dialog = flow.querySelector('[data-booking-dialog]');
    const search = flow.querySelector('[data-booking-search]');
    const options = [...flow.querySelectorAll('[data-booking-option]')];
    const methodButtons = [...flow.querySelectorAll('[data-booking-open]')];
    const continueButton = flow.querySelector('[data-booking-continue]');
    const selectionCard = flow.querySelector('[data-booking-selection]');
    const error = flow.querySelector('[data-booking-flow-error]');
    const selectionProgress = flow.querySelector('[data-booking-progress-selection]');
    const scheduleProgress = flow.querySelector('[data-booking-progress-schedule]');
    const cards = [...root.querySelectorAll('[data-doctor-card]')];
    const noResults = root.querySelector('[data-booking-no-results]');
    let activeKind = '';
    let selection = null;

    if (!selectionStep || !scheduleStep || !dialog || !continueButton) return;

    const renderOptions = () => {
        const query = search?.value ?? '';
        let visibleCount = 0;
        options.forEach((option) => {
            const visible = option.dataset.kind === activeKind
                && bookingOptionMatches({
                    name: option.dataset.name,
                    detail: option.dataset.detail,
                    search: option.dataset.search,
                    kind: option.dataset.kind,
                }, query);
            option.hidden = !visible;
            option.setAttribute('aria-pressed', String(selection?.kind === option.dataset.kind && String(selection?.id) === option.dataset.id));
            if (visible) visibleCount++;
        });
        flow.querySelector('[data-booking-option-count]').textContent = visibleCount
            ? `${visibleCount} lựa chọn`
            : '';
        flow.querySelector('[data-booking-options-empty]').hidden = visibleCount > 0;
    };

    const clearSlotChoices = () => {
        cards.forEach((card) => {
            card.querySelectorAll('[data-slot].is-selected').forEach((slot) => {
                slot.classList.remove('is-selected');
                slot.setAttribute('aria-pressed', 'false');
            });
            const action = card.querySelector('[data-booking-action]');
            const summary = card.querySelector('[data-booking-summary]');
            if (action) {
                action.removeAttribute('href');
                action.setAttribute('aria-disabled', 'true');
                action.setAttribute('tabindex', '-1');
                action.classList.add('is-disabled');
            }
            if (summary) summary.textContent = 'Chọn một khung giờ còn nhận lịch ở trên để tiếp tục đặt lịch.';
        });
    };

    const applyScheduleFilter = () => {
        if (!selection) return;
        let anyVisible = false;

        cards.forEach((card) => {
            const slots = [...card.querySelectorAll('[data-slot]')];
            slots.forEach((slot) => {
                const matches = bookingSlotMatches({
                    doctorId: slot.dataset.doctorId,
                    specialtyId: slot.dataset.specialtyId,
                    roomId: slot.dataset.roomId,
                }, selection);
                slot.hidden = !matches;
                if (!matches && slot.classList.contains('is-selected')) clearSlotChoices();
            });

            card.querySelectorAll('[data-shift], [data-period], [data-day-panel]').forEach((group) => {
                group.hidden = !group.querySelector('[data-slot]:not([hidden])');
            });

            const dayPanels = [...card.querySelectorAll('[data-day-panel]')];
            dayPanels.forEach((panel) => {
                const tab = [...card.querySelectorAll('[data-day-tab]')].find((item) => item.dataset.day === panel.dataset.dayPanel);
                if (tab) tab.hidden = panel.hidden;
            });

            const visiblePanels = dayPanels.filter((panel) => !panel.hidden);
            if (visiblePanels.length && !visiblePanels.some((panel) => panel.classList.contains('is-active'))) {
                visiblePanels.forEach((panel, index) => panel.classList.toggle('is-active', index === 0));
                card.querySelectorAll('[data-day-tab]').forEach((tab) => {
                    const isActive = !tab.hidden && tab.dataset.day === visiblePanels[0].dataset.dayPanel;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-pressed', String(isActive));
                });
            }

            const visibleSlots = slots.filter((slot) => !slot.hidden);
            card.hidden = visibleSlots.length === 0;
            anyVisible ||= visibleSlots.length > 0;

            card.querySelectorAll('[data-period]').forEach((period) => {
                const available = [...period.querySelectorAll('[data-slot]:not([hidden])')].filter((slot) => !slot.disabled).length;
                const badge = period.querySelector('[data-period-badge]');
                if (badge) badge.textContent = available ? `Còn ${available} khung giờ` : 'Ngừng nhận lịch';
            });
        });

        if (noResults) noResults.hidden = anyVisible;
    };

    const setSelection = (option) => {
        selection = { kind: option.dataset.kind, id: option.dataset.id };
        methodButtons.forEach((button) => button.classList.toggle('is-selected', button.dataset.bookingOpen === selection.kind));
        selectionCard.hidden = false;
        flow.querySelector('[data-booking-selected-kind]').textContent = BOOKING_KIND_LABELS[selection.kind];
        flow.querySelector('[data-booking-selected-name]').textContent = option.dataset.name;
        flow.querySelector('[data-booking-selected-detail]').textContent = option.dataset.detail;
        continueButton.disabled = false;
        error.hidden = true;
        clearSlotChoices();
        renderOptions();
    };

    const openDialog = (kind) => {
        activeKind = kind;
        const titles = { specialty: 'Chọn chuyên khoa', doctor: 'Chọn bác sĩ', room: 'Chọn phòng khám' };
        flow.querySelector('[data-booking-dialog-title]').textContent = titles[kind];
        search.value = '';
        renderOptions();
        dialog.showModal();
        requestAnimationFrame(() => search.focus());
    };

    const showSelectionStep = (reopenCurrentMethod = false) => {
        scheduleStep.hidden = true;
        selectionStep.hidden = false;
        selectionProgress.setAttribute('aria-disabled', 'true');
        selectionProgress.removeAttribute('role');
        selectionProgress.removeAttribute('tabindex');
        selectionProgress.classList.add('is-active');
        selectionProgress.setAttribute('aria-current', 'step');
        scheduleProgress.classList.remove('is-active');
        scheduleProgress.removeAttribute('aria-current');
        selectionStep.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if (reopenCurrentMethod) {
            const kindToReopen = bookingChangeKind(selection);
            if (kindToReopen) openDialog(kindToReopen);
        }
    };

    methodButtons.forEach((button) => button.addEventListener('click', () => openDialog(button.dataset.bookingOpen)));
    options.forEach((option) => option.addEventListener('click', () => {
        setSelection(option);
        dialog.close();
    }));
    search.addEventListener('input', renderOptions);
    flow.querySelector('[data-booking-dialog-close]').addEventListener('click', () => dialog.close());

    selectionProgress.addEventListener('click', () => {
        if (selectionProgress.getAttribute('aria-disabled') !== 'true') showSelectionStep();
    });
    selectionProgress.addEventListener('keydown', (event) => {
        if (selectionProgress.getAttribute('aria-disabled') === 'true') return;
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            showSelectionStep();
        }
    });
    root.querySelectorAll('[data-booking-change]').forEach((button) => button.addEventListener('click', () => showSelectionStep(true)));

    continueButton.addEventListener('click', () => {
        if (!selection) {
            error.hidden = false;
            return;
        }
        const chosenOption = options.find((option) => option.dataset.kind === selection.kind && option.dataset.id === selection.id);
        root.querySelector('[data-booking-result-summary]').textContent = `${BOOKING_KIND_LABELS[selection.kind]}: ${chosenOption?.dataset.name ?? ''} · ${chosenOption?.dataset.detail ?? ''}`;
        selectionStep.hidden = true;
        scheduleStep.hidden = false;
        selectionProgress.setAttribute('aria-disabled', 'false');
        selectionProgress.setAttribute('role', 'button');
        selectionProgress.setAttribute('tabindex', '0');
        selectionProgress.classList.remove('is-active');
        selectionProgress.removeAttribute('aria-current');
        scheduleProgress.classList.add('is-active');
        scheduleProgress.setAttribute('aria-current', 'step');
        applyScheduleFilter();
        scheduleStep.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    const initialKind = flow.dataset.initialKind;
    const initialId = flow.dataset.initialId;
    if (initialKind && initialId) {
        const initialOption = options.find((option) => option.dataset.kind === initialKind && option.dataset.id === initialId);
        if (initialOption) setSelection(initialOption);
    }
}
