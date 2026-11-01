<?php
return [
    'self_service_approved' =>
        env('APP_ENV', 'production') !== 'testing'
        && filter_var(
            env('PATIENT_APPOINTMENT_SELF_SERVICE_APPROVED', true),
            FILTER_VALIDATE_BOOLEAN
        ),
    'cancel_cutoff_minutes' => env(
        'PATIENT_APPOINTMENT_CANCEL_CUTOFF_MINUTES', 120
    ),
    'reschedule_cutoff_minutes' => env(
        'PATIENT_APPOINTMENT_RESCHEDULE_CUTOFF_MINUTES', 120
    ),
];
