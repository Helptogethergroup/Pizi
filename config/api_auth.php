<?php

// Settings for the v2 (session based) API authentication used by the
// native owner app. The original /api/auth/* login is left untouched.
return [
    // Short on purpose: a stolen access token is only useful for minutes.
    'access_ttl_seconds' => (int) env('API_ACCESS_TTL', 900),

    // Refresh tokens rotate on every use, and slide forward while the app is used.
    'refresh_ttl_days' => (int) env('API_REFRESH_TTL_DAYS', 30),

    // Failed password / OTP attempts for one account before it is locked.
    'max_failures' => (int) env('API_MAX_FAILURES', 8),
    'lockout_minutes' => (int) env('API_LOCKOUT_MINUTES', 15),

    // Password login on a device this account has not used before needs an OTP too.
    'require_otp_on_new_device' => (bool) env('API_OTP_ON_NEW_DEVICE', true),

    // Only these roles may sign in through the owner app.
    'allowed_roles' => ['owner'],
];
