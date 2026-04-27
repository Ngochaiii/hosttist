<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'renewal' => [
        // Số ngày trước expiry mà ta đặt next_renewal_date (mốc đầu tiên để nhắc)
        'reminder_lead_days' => (int) env('RENEWAL_REMINDER_LEAD_DAYS', 7),
        // Các mốc gửi email nhắc (số ngày trước expiry). Phải khớp với cột notified_*d_at.
        'reminder_milestones' => [30, 15, 7, 1],
    ],

];
