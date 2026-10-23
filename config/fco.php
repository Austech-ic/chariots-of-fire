<?php

/*
 * Flaming Chariot Outreach application configuration.
 * Secrets live in .env only — never commit them.
 */
return [

    // Application release (shown on System status; bump with each release zip).
    'version' => '1.2.0',

    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Africa/Lagos'),

    // Force staff to set up authenticator-app two-factor authentication before using the CMS.
    'require_staff_mfa' => env('REQUIRE_STAFF_MFA', false),

    'installer_enabled' => env('INSTALLER_ENABLED', true),

    'installed' => env('APP_INSTALLED', false),

    // Extra CSP sources, e.g. for an analytics provider: "https://plausible.io".
    'csp_extra_script_src' => env('CSP_EXTRA_SCRIPT_SRC', ''),
    'csp_extra_connect_src' => env('CSP_EXTRA_CONNECT_SRC', ''),
    'analytics_script_url' => env('ANALYTICS_SCRIPT_URL'),
    'analytics_domain' => env('ANALYTICS_DOMAIN'),

    'staff_notification_email' => env('STAFF_NOTIFICATION_EMAIL'),

    'media' => [
        'allowed' => [
            'image' => [
                'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'],
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'],
                'max_kb' => (int) env('MEDIA_MAX_IMAGE_KB', 15360),
            ],
            'audio' => [
                'mimes' => ['audio/mpeg', 'audio/mp3', 'audio/mp4', 'audio/x-m4a', 'audio/aac', 'audio/ogg', 'audio/wav', 'audio/x-wav'],
                'extensions' => ['mp3', 'm4a', 'aac', 'ogg', 'oga', 'wav'],
                'max_kb' => (int) env('MEDIA_MAX_AUDIO_KB', 204800),
            ],
            'video' => [
                'mimes' => ['video/mp4', 'video/webm', 'video/quicktime'],
                'extensions' => ['mp4', 'webm', 'mov'],
                'max_kb' => (int) env('MEDIA_MAX_VIDEO_KB', 512000),
            ],
            'document' => [
                'mimes' => ['application/pdf'],
                'extensions' => ['pdf'],
                'max_kb' => (int) env('MEDIA_MAX_DOCUMENT_KB', 51200),
            ],
        ],
        'image_variants' => ['sm' => 640, 'md' => 1280, 'lg' => 1920],
    ],

    'streaming' => [
        'youtube_api_key' => env('YOUTUBE_API_KEY'),
        'youtube_channel_id' => env('YOUTUBE_CHANNEL_ID'),
        // Hosts permitted for the generic "embed URL" provider (also added to the CSP).
        'embed_hosts' => array_filter(explode(',', env('STREAM_EMBED_HOSTS', 'stream.mux.com,cloudflarestream.com,videodelivery.net,facebook.com'))),
    ],

    'payments' => [
        // paystack | flutterwave | test
        'default' => env('PAYMENT_PROVIDER', 'test'),
        // Receipt address used when a donor gives anonymously without an email (provider requirement).
        'fallback_email' => env('PAYMENT_FALLBACK_EMAIL'),
        'paystack' => [
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'currencies' => array_filter(explode(',', env('PAYSTACK_CURRENCIES', 'NGN'))),
        ],
        'flutterwave' => [
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'webhook_hash' => env('FLUTTERWAVE_WEBHOOK_HASH'),
            'currencies' => array_filter(explode(',', env('FLUTTERWAVE_CURRENCIES', 'NGN,USD'))),
        ],
        'test' => [
            // The simulated gateway is never available in production.
            'enabled' => env('PAYMENT_TEST_GATEWAY', env('APP_ENV') !== 'production'),
            'webhook_secret' => env('PAYMENT_TEST_WEBHOOK_SECRET', 'test-secret'),
        ],
        'min_amount_minor' => (int) env('DONATION_MIN_MINOR', 10000),
        'max_amount_minor' => (int) env('DONATION_MAX_MINOR', 100000000000),
    ],

    'messaging' => [
        // log | termii | twilio
        'sms_driver' => env('SMS_DRIVER', 'log'),
        // log | whatsapp_cloud | twilio
        'whatsapp_driver' => env('WHATSAPP_DRIVER', 'log'),
        'termii' => ['api_key' => env('TERMII_API_KEY'), 'sender_id' => env('TERMII_SENDER_ID')],
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'sms_from' => env('TWILIO_SMS_FROM'),
            'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        ],
        'whatsapp_cloud' => [
            'token' => env('WHATSAPP_CLOUD_TOKEN'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            // Map internal template keys to Meta-approved template names.
            'templates' => [
                'appointment_reminder' => ['name' => env('WHATSAPP_TPL_APPOINTMENT_REMINDER', 'appointment_reminder'), 'language' => 'en'],
                'event_reminder' => ['name' => env('WHATSAPP_TPL_EVENT_REMINDER', 'event_reminder'), 'language' => 'en'],
            ],
        ],
        // Plain-text bodies (SMS). Keep free of sensitive information.
        'templates' => [
            'appointment_reminder' => 'Reminder: your appointment {reference} is on {when}. Reply STOP to opt out.',
            'event_reminder' => '{title} starts {when}. Details: {url}',
        ],
    ],

    'newsletter' => [
        // none | mailchimp | brevo — the application remains the source of truth for consent.
        'provider' => env('NEWSLETTER_PROVIDER', 'none'),
        'mailchimp' => ['api_key' => env('MAILCHIMP_API_KEY'), 'list_id' => env('MAILCHIMP_LIST_ID')],
        'brevo' => ['api_key' => env('BREVO_API_KEY'), 'list_id' => env('BREVO_LIST_ID')],
    ],

    // Nightly backups (storage/app/backups). Download them off-server regularly.
    'backup' => [
        'password' => env('BACKUP_PASSWORD'),
        'keep' => (int) env('BACKUP_KEEP', 14),
        'include_media' => (bool) env('BACKUP_INCLUDE_MEDIA', false),
        'exclude_tables' => ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations'],
    ],

    // Retention periods (days). Review with the ministry's data protection adviser.
    'retention' => [
        'counselling_closed_days' => (int) env('RETENTION_COUNSELLING_DAYS', 730),
        'appointment_contact_days' => (int) env('RETENTION_APPOINTMENT_DAYS', 365),
        'enquiry_days' => (int) env('RETENTION_ENQUIRY_DAYS', 365),
        'webhook_days' => (int) env('RETENTION_WEBHOOK_DAYS', 180),
        'notification_log_days' => (int) env('RETENTION_NOTIFICATION_LOG_DAYS', 180),
    ],

    'booking' => [
        'reminder_hours_before' => (int) env('APPOINTMENT_REMINDER_HOURS', 24),
    ],
];
