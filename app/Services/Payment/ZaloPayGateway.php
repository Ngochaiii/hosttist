<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Orders;
use Illuminate\Http\Request;

/**
 * ZaloPayGateway — tích hợp cổng thanh toán ZaloPay
 *
 * TODO (Phase 3): Fill implement khi có API key từ ZaloPay
 * Tài liệu: https://docs.zalopay.vn/
 *
 * Các thông số cần thêm vào config/payment.php:
 *   zalopay.app_id    — App ID
 *   zalopay.key1      — Key1 để tạo MAC
 *   zalopay.key2      — Key2 để verify callback
 *   zalopay.endpoint  — API endpoint
 *   zalopay.callback_url — URL nhận callback từ ZaloPay
 */
class ZaloPayGateway implements PaymentGatewayInterface
{
    public function createPaymentUrl(Orders $order): string
    {
        // TODO Phase 3: Build ZaloPay order và lấy order_url
        // Gọi POST /v2/create với mac = HMAC_SHA256(data, key1)
        throw new \RuntimeException('ZaloPay chưa được tích hợp. Liên hệ admin để biết thêm.');
    }

    public function verifyWebhook(Request $request): bool
    {
        // TODO Phase 3: Verify mac = HMAC_SHA256(data, key2)
        throw new \RuntimeException('ZaloPay chưa được tích hợp.');
    }

    public function getTransactionStatus(string $transactionId): string
    {
        // TODO Phase 3: Gọi ZaloPay Query API
        throw new \RuntimeException('ZaloPay chưa được tích hợp.');
    }

    public function getProviderName(): string
    {
        return 'zalopay';
    }
}
