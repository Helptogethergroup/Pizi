<?php

return [
    'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 10),
    'resend_seconds' => env('OTP_RESEND_SECONDS', 60),
    'max_attempts'   => env('OTP_MAX_ATTEMPTS', 3),
];
