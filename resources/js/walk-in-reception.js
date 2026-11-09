const walkInForm = document.querySelector('[data-walk-in-form]');
if (walkInForm) {
    const patientMode = walkInForm.elements.namedItem('patient_mode');
    const patientSelect = walkInForm.elements.namedItem('patient_id');
    const citizenId = walkInForm.elements.namedItem('citizen_id_number');
    const phone = walkInForm.elements.namedItem('phone');
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

        profileFields.forEach((field) => {
            if (existing) {
                if (!showingExisting) newPatientDraft.set(field, field.value);
                if (field !== citizenId || !needsCitizenId || changedPatient) {
                    field.value = profile?.[field.dataset.patientField] ?? '';
                }
            } else if (showingExisting) {
                field.value = newPatientDraft.get(field) ?? '';
            }

            if (field instanceof HTMLSelectElement) field.disabled = existing;
            else field.readOnly = existing;
        });
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
