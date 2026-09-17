<?php

return [
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    // WhatsApp Manager → API Setup page par "WhatsApp Business Account ID" —
    // Phone Number ID ke bilkul upar/neeche hi dikhta hai usi page par.
    'waba_id' => env('WHATSAPP_WABA_ID'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v20.0'),

    // Set true only after the "Pay Now" button URL in Meta Business Manager is
    // edited to Dynamic (https://pizi.in/pay/{{1}}) and re-approved.
    'button_is_dynamic' => env('WHATSAPP_BUTTON_IS_DYNAMIC', false),

    // Template names + language must exactly match Meta Business Manager
    // Lang: check business.facebook.com → WhatsApp → Message Templates
    'templates' => [
        'bill_generated'    => ['name' => env('WHATSAPP_TEMPLATE_BILL_GENERATED',    'rent_bill_generated_v3'), 'lang' => 'en'],
        'due_reminder'      => ['name' => env('WHATSAPP_TEMPLATE_DUE_REMINDER',      'rent_due_reminder'),      'lang' => 'en'],
        'payment_confirmed' => ['name' => env('WHATSAPP_TEMPLATE_PAYMENT_CONFIRMED', 'rent_payment_confirmed'), 'lang' => 'en'],
        'kyc_reminder'      => ['name' => env('WHATSAPP_TEMPLATE_KYC_REMINDER',      'tenant_kyc_reminder'),    'lang' => 'en'],
        'account_create'    => ['name' => env('WHATSAPP_TEMPLATE_ACCOUNT_CREATE',    'account_create'),         'lang' => 'en_US'],
        'pg_owner_welcome'  => ['name' => env('WHATSAPP_TEMPLATE_PG_OWNER_WELCOME',  'pg_owenr_welcome'),       'lang' => 'en'],
        'pg_not_listed'     => ['name' => env('WHATSAPP_TEMPLATE_PG_NOT_LISTED',     'owner_signup_but_pg_not_regesterd_'), 'lang' => 'en'],
        'new_lead_alert'    => ['name' => env('WHATSAPP_TEMPLATE_NEW_LEAD_ALERT',    'new_lead_alert_v2'), 'lang' => 'en'],

        // Approved 2026-09-01 — new round
        'vacant_room_alert'                => ['name' => 'vacant_room_alert', 'lang' => 'en'],
        'property_verified_alert'          => ['name' => 'property_verified_alert', 'lang' => 'en'],
        'tenant_rent_overdue_owner_alert'  => ['name' => 'tenant_rent_overdue_owner_alert', 'lang' => 'en'],
        'wallet_low_balance'               => ['name' => 'wallet_low_balance', 'lang' => 'en'],
        'complaint_resolved'               => ['name' => 'complaint_resolved', 'lang' => 'en'],
        'agreement_renewal_reminder'       => ['name' => 'agreement_renewal_reminder', 'lang' => 'en'],
        'move_out_refund_status'           => ['name' => 'move_out_refund_status', 'lang' => 'en'],
        'admin_daily_summary'              => ['name' => 'admin_daily_summary', 'lang' => 'en'],
        'payment_gateway_failure_alert'    => ['name' => 'payment_gateway_failure_alert', 'lang' => 'en'],

        // Document-header template for auto-emailed/WhatsApp'd invoices.
        // Must be submitted+approved in Meta with a DOCUMENT header component.
        'invoice_ready'                    => ['name' => 'invoice_ready', 'lang' => 'en'],

        // Sent when admin manually credits an owner's wallet (e.g. the
        // one-time 500-credit free bonus rollout).
        'free_credit_bonus'                => ['name' => 'credit_wallet_update', 'lang' => 'en'],

        // Daily end-of-day count of leads an owner received — only sent to
        // owners who got at least 1 lead that day (see SendOwnerDailyLeadCount).
        'owner_daily_lead_count'           => ['name' => 'owner_daily_lead_count', 'lang' => 'en'],
    ],
];
