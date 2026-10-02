<?php

return [
    'razorpay' => [
        'key'        => env('RAZORPAY_KEY_ID'),      // legacy key — kept for blade views
        'secret'     => env('RAZORPAY_KEY_SECRET'),
        'key_id'     => env('RAZORPAY_KEY_ID'),      // used in controllers via config()
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
    ],
    
'setu' => [
        'client_id'           => env('SETU_CLIENT_ID'),
        'client_secret'       => env('SETU_CLIENT_SECRET'),
        'product_instance_id' => env('SETU_ESIGN_PRODUCT_INSTANCE_ID'),
        // Separate Setu product from eSign — Aadhaar OKYC / Aadhaar
        // Verification, has its own Product Instance ID from Setu's dashboard.
        'kyc_product_instance_id' => env('SETU_KYC_PRODUCT_INSTANCE_ID'),
        'base_url'            => env('SETU_BASE_URL', 'https://dg-sandbox.setu.co'),
        'redirect_url'        => env('SETU_REDIRECT_URL'),
    ],

    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', 'https://pizi.in/auth/google/callback'),
    ],

    // Firebase Cloud Messaging (push notifications for the mobile apps).
    // Absolute path to the Firebase service-account JSON — kept OUTSIDE the
    // repo (e.g. ~/env-store/firebase-service-account.json). Push silently
    // no-ops until this is set and the file exists.
    'fcm' => [
        'credentials' => env('FCM_CREDENTIALS_PATH'),
    ],

];