export function doctorMatchesSpecialty(doctor, specialtyId) {
    return !!doctor && !!doctor.specialtyId && (doctor.isGeneral === '1' || String(doctor.specialtyId) === String(specialtyId));
}

export function initializeDoctorSpecialtyPairs(root = document) {
    root.querySelectorAll('form').forEach((form) => {
        const doctor = form.querySelector('select[name="doctor_id"]');
        const specialty = form.querySelector('select[name="specialty_id"]');
        if (!doctor || !specialty || form.dataset.specialtyPairReady) return;
        form.dataset.specialtyPairReady = 'true';
        let syncing = false;
        const selectedDoctor = () => doctor.selectedOptions[0]?.value ? doctor.selectedOptions[0].dataset : null;
        const refresh = (changed = '') => {
            if (syncing) return;
            syncing = true;
            const current = selectedDoctor();
            if (changed === 'specialty' && specialty.value && doctor.value && !doctorMatchesSpecialty(current, specialty.value)) doctor.value = '';
            if (changed === 'doctor' && doctor.value && current?.specialtyId && current.isGeneral !== '1') {
                if (form.method?.toLowerCase() !== 'get') {
                    specialty.value = [...specialty.options].some((option) => option.value === current.specialtyId) ? current.specialtyId : '';
                } else if (specialty.value && !doctorMatchesSpecialty(current, specialty.value)) {
                    specialty.value = '';
                }
            }
            const chosen = selectedDoctor();
            [...doctor.options].forEach((option) => {
                const blocked = option.value && specialty.value && !doctorMatchesSpecialty(option.dataset, specialty.value);
                option.hidden = !!blocked && option.value !== doctor.value;
                option.disabled = option.hidden;
            });
            [...specialty.options].forEach((option) => {
                const blocked = option.value && doctor.value && !doctorMatchesSpecialty(chosen, option.value);
                option.hidden = !!blocked && option.value !== specialty.value;
                option.disabled = option.hidden;
            });
            doctor.dispatchEvent(new Event('admin:options-changed'));
            specialty.dispatchEvent(new Event('admin:options-changed'));
            syncing = false;
        };
        doctor.addEventListener('change', () => refresh('doctor'));
        specialty.addEventListener('change', () => refresh('specialty'));
        form.addEventListener('reset', () => setTimeout(() => refresh(), 0));
        refresh();
    });
}
