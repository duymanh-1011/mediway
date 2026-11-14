const walkInForm = document.querySelector('[data-walk-in-form]');
if (walkInForm) {
    const patientMode = walkInForm.elements.namedItem('patient_mode');
    const patientSelect = walkInForm.elements.namedItem('patient_id');
    const citizenId = walkInForm.elements.namedItem('citizen_id_number');
    const phone = walkInForm.elements.namedItem('phone');
    const fullName = walkInForm.elements.namedItem('full_name');
    const birthDate = walkInForm.elements.namedItem('date_of_birth');
    const gender = walkInForm.elements.namedItem('gender');
    const profileFields = walkInForm.querySelectorAll('[data-patient-field]');
    const confirmSearch = walkInForm.elements.namedItem('confirm_search');
    const newPatientDraft = new Map();
    const phoneDrafts = new Map();
    let showingExisting = patientMode.value === 'existing';
    let selectedKey = showingExisting ? patientSelect.value : 'new';

    function syncPatientSelection() {
        const existing = patientMode.value === 'existing';
        const profile = patientSelect.selectedOptions[0]?.dataset;
        const nextKey = existing ? patientSelect.value : 'new';
        const changedPatient = selectedKey !== nextKey;
        const needsAccount = !existing || (patientSelect.value !== '' && profile?.hasAccount === '0');
        const needsCitizenId = needsAccount && (!existing || !profile?.citizenId);
        const needsBirthDate = existing && !profile?.birthDate;
        const needsGender = existing && !profile?.gender;

        profileFields.forEach((field) => {
            if (existing) {
                if (!showingExisting) newPatientDraft.set(field, field.value);
                if (field !== citizenId || !needsCitizenId || changedPatient) {
                    field.value = profile?.[field.dataset.patientField] ?? '';
                }
            } else if (showingExisting) {
                field.value = newPatientDraft.get(field) ?? '';
            }

            if (field instanceof HTMLSelectElement) field.disabled = existing && !(field === gender && needsGender);
            else field.readOnly = existing && !(field === birthDate && needsBirthDate);
        });
        fullName.required = !existing;
        birthDate.required = !existing || needsBirthDate;
        gender.required = !existing || needsGender;
        if (changedPatient) {
            if (!phone.readOnly) phoneDrafts.set(selectedKey, phone.value);
            phone.value = needsAccount ? (phoneDrafts.get(nextKey) ?? '') : (profile?.phone ?? '');
        }
        phone.readOnly = !needsAccount;
        phone.required = needsAccount;
        patientSelect.disabled = !existing;
        citizenId.readOnly = !needsCitizenId;
        citizenId.required = needsCitizenId;
        confirmSearch.disabled = existing;
        showingExisting = existing;
        selectedKey = nextKey;
    }

    patientSelect.required = true;
    walkInForm.addEventListener('change', (event) => {
        if (event.target.name === 'patient_mode' || event.target === patientSelect) syncPatientSelection();
    });
    window.addEventListener('pageshow', syncPatientSelection);
    document.querySelector('[data-walk-in-choose]')?.addEventListener('click', () => {
        patientMode.value = 'existing';
        syncPatientSelection();
        patientSelect.focus({ preventScroll: true });
    });
    syncPatientSelection();
}

const receptionForm = document.querySelector('[data-walk-in-form]');
if (receptionForm) {
    const options = [...receptionForm.querySelectorAll('[data-room-option]')];
    const filters = Object.fromEntries([...receptionForm.querySelectorAll('[data-room-filter]')]
        .map((filter) => [filter.dataset.roomFilter, filter]));
    const noMatch = receptionForm.querySelector('[data-no-room-match]');
    const submit = receptionForm.querySelector('[data-walk-in-submit]');
    const updateRoomOptions = () => {
        let visibleCount = 0;
        options.forEach((option) => {
            const matches = ['room', 'specialty', 'doctor'].every((key) => !filters[key]?.value
                || filters[key].value === option.dataset[`${key}Id`]);
            option.hidden = !matches;
            option.classList.toggle('is-selected', matches && option.querySelector('input[type="radio"]').checked);
            if (matches) visibleCount += 1;
            if (!matches && option.querySelector('input[type="radio"]').checked) option.querySelector('input[type="radio"]').checked = false;
        });
        if (noMatch) noMatch.hidden = visibleCount > 0 || options.length === 0;
        if (submit) submit.disabled = visibleCount === 0;
    };
    Object.values(filters).forEach((filter) => filter.addEventListener('change', updateRoomOptions));
    receptionForm.addEventListener('change', (event) => {
        if (event.target.matches('[data-room-option] input[type="radio"]')) updateRoomOptions();
    });
    updateRoomOptions();
}
