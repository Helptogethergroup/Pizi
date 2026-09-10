<?php

return [
    // ── Meta (Facebook/Instagram) Lead Ads ──────────────────────────────
    // Verify token: any string you invent — you'll type the SAME value
    // into the Meta webhook subscription screen when setting it up.
    'meta_verify_token' => env('META_LEADS_VERIFY_TOKEN'),

    // Page Access Token with the `leads_retrieval` + `pages_manage_ads`
    // permissions — generated from Meta Business Suite for the Facebook
    // Page running the ads. This is DIFFERENT from the WhatsApp Cloud API
    // token used elsewhere in this app.
    'meta_page_access_token' => env('META_LEADS_PAGE_ACCESS_TOKEN'),

    'meta_api_version' => env('META_LEADS_API_VERSION', 'v20.0'),

    // ── Google Ads Lead Form Extensions ─────────────────────────────────
    // Any secret string you invent — you'll paste the SAME value into
    // Google Ads' "Webhook" lead delivery setup screen. Google sends it
    // back on every lead so we can verify the request is genuinely theirs.
    'google_webhook_key' => env('GOOGLE_ADS_LEADS_WEBHOOK_KEY'),
];
