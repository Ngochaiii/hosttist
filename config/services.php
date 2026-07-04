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

    'renewal' => [
        // Số ngày trước expiry mà ta đặt next_renewal_date (mốc đầu tiên để nhắc)
        'reminder_lead_days' => (int) env('RENEWAL_REMINDER_LEAD_DAYS', 7),
        // Các mốc nhắc hết hạn (số ngày trước expiry). Phải khớp với cột notified_*d_at.
        'reminder_milestones' => [30, 15, 7, 1],
    ],

];
