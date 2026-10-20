<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media library
    |--------------------------------------------------------------------------
    |
    | Public website media is stored on MEDIA_DISK (the "public" disk by
    | default; switch to "s3" for object storage). Sizes are in kilobytes.
    | Additional hosts allowed in rich-text image sources (for example a CDN)
    | can be listed in MEDIA_EXTRA_HOSTS as a comma separated list.
    |
    */

    'media' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'variant_widths' => [480, 960, 1600, 2400],
        // Stored originals are scaled down to this longest edge (plenty for full-screen heroes).
        'max_dimension' => 3200,
        'max_pixels' => 60_000_000,
        // Memory available while decoding and resizing an image upload.
        'processing_memory' => env('MEDIA_PROCESSING_MEMORY', '512M'),
        'extra_hosts' => array_filter(explode(',', (string) env('MEDIA_EXTRA_HOSTS', ''))),
        'types' => [
            'image' => ['extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'], 'max_kb' => 10240],
            'video' => ['extensions' => ['mp4', 'webm'], 'max_kb' => 51200],
            'document' => ['extensions' => ['pdf'], 'max_kb' => 20480],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Recruitment
    |--------------------------------------------------------------------------
    |
    | CVs are stored on a private disk that is never web accessible.
    |
    */

    'careers' => [
        'cv_disk' => env('CV_DISK', 'local'),
        'cv_extensions' => ['pdf', 'doc', 'docx'],
        'cv_max_kb' => 5120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Spam protection
    |--------------------------------------------------------------------------
    |
    | Public forms always use a honeypot, a minimum completion time and rate
    | limiting. A CAPTCHA can be enabled by setting CAPTCHA_DRIVER=turnstile
    | together with Cloudflare Turnstile keys.
    |
    */

    'spam' => [
        'minimum_seconds' => (int) env('FORM_MINIMUM_SECONDS', 3),
        'captcha' => [
            'driver' => env('CAPTCHA_DRIVER', 'none'),
            'turnstile' => [
                'site_key' => env('TURNSTILE_SITE_KEY'),
                'secret_key' => env('TURNSTILE_SECRET_KEY'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback notification recipients
    |--------------------------------------------------------------------------
    |
    | Used when no recipients have been configured in Settings → Notifications.
    |
    */

    'notifications' => [
        'fallback_recipient' => env('NOTIFICATION_FALLBACK_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security headers
    |--------------------------------------------------------------------------
    */

    'security' => [
        'hsts' => (bool) env('SECURITY_HSTS', false),
        'csp' => (bool) env('SECURITY_CSP', true),
    ],

    'preview_link_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Queue processing
    |--------------------------------------------------------------------------
    |
    | When true, the scheduler runs `queue:work --stop-when-empty` every minute.
    | This suits shared hosting where a long-running worker is not available.
    | Set QUEUE_WORK_VIA_SCHEDULER=false if a supervisor-managed worker runs.
    |
    */

    'queue_via_scheduler' => (bool) env('QUEUE_WORK_VIA_SCHEDULER', true),

    /*
    |--------------------------------------------------------------------------
    | Local development administrator
    |--------------------------------------------------------------------------
    |
    | Created by `php artisan db:seed` when APP_ENV=local and both values are
    | set. Never used in any other environment. There is deliberately no
    | fallback password in code.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Client onboarding questionnaires
    |--------------------------------------------------------------------------
    |
    | Client uploads are stored on a PRIVATE disk and served only through
    | the questionnaire link or the admin. Never point this at a public disk.
    |
    */

    'onboarding' => [
        'disk' => env('ONBOARDING_DISK', 'local'),
        'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'],
        'max_kb' => 10240,
        'max_files' => 15,
        'link_days' => 30,
    ],

    'local_admin' => [
        'name' => env('LOCAL_ADMIN_NAME', 'Local Administrator'),
        'email' => env('LOCAL_ADMIN_EMAIL'),
        'password' => env('LOCAL_ADMIN_PASSWORD'),
    ],

];
