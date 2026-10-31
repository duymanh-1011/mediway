<?php

return [
    'enabled' => filter_var(
        env('APPOINTMENTS_AUTO_CANCEL_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'grace_minutes' => max(
        0,
        min(
            1440,
            (int) env(
                'APPOINTMENTS_NO_SHOW_GRACE_MINUTES',
                30
            )
        )
    ),
];
