<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    | Giá trị: manual | vnpay | momo | zalopay
    | Thay đổi sau khi có API key từ provider.
    */
    'default_gateway' => env('PAYMENT_GATEWAY', 'manual'),

    /*
    |--------------------------------------------------------------------------
    | VNPay Configuration
    |--------------------------------------------------------------------------
    | Điền khi nhận được API key từ VNPay.
    | Sandbox: https://sandbox.vnpayment.vn/apis/
    */
    'vnpay' => [
        'tmn_code'    => env('VNPAY_TMN_CODE', ''),
        'hash_secret' => env('VNPAY_HASH_SECRET', ''),
        'url'         => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'return_url'  => env('VNPAY_RETURN_URL', ''),
        'notify_url'  => env('VNPAY_NOTIFY_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | MoMo Configuration
    |--------------------------------------------------------------------------
    | Điền khi nhận được API key từ MoMo.
    | Tài liệu: https://developers.momo.vn/
    */
    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE', ''),
        'access_key'   => env('MOMO_ACCESS_KEY', ''),
        'secret_key'   => env('MOMO_SECRET_KEY', ''),
        'endpoint'     => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'redirect_url' => env('MOMO_REDIRECT_URL', ''),
        'notify_url'   => env('MOMO_NOTIFY_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | ZaloPay Configuration
    |--------------------------------------------------------------------------
    | Điền khi nhận được API key từ ZaloPay.
    | Tài liệu: https://docs.zalopay.vn/
    */
    'zalopay' => [
        'app_id'       => env('ZALOPAY_APP_ID', ''),
        'key1'         => env('ZALOPAY_KEY1', ''),
        'key2'         => env('ZALOPAY_KEY2', ''),
        'endpoint'     => env('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn/v2/create'),
        'callback_url' => env('ZALOPAY_CALLBACK_URL', ''),
    ],

];
