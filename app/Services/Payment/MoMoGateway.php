<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Orders;
use Illuminate\Http\Request;

/**
 * MoMoGateway — tích hợp cổng thanh toán MoMo
 *
 * TODO (Phase 3): Fill implement khi có API key từ MoMo
 * Tài liệu: https://developers.momo.vn/
 *
 * Các thông số cần thêm vào config/payment.php:
 *   momo.partner_code — Partner Code
 *   momo.access_key   — Access Key
 *   momo.secret_key   — Secret Key
 *   momo.endpoint     — API endpoint (sandbox/production)
 *   momo.redirect_url — URL redirect sau khi thanh toán
 *   momo.notify_url   — URL webhook nhận callback
 */
class MoMoGateway implements PaymentGatewayInterface
{
    public function createPaymentUrl(Orders $order): string
    {
        // TODO Phase 3: Build MoMo payment URL
        // Gọi MoMo API /v2/gateway/api/create để lấy payUrl
        throw new \RuntimeException('MoMo chưa được tích hợp. Liên hệ admin để biết thêm.');
    }

    public function verifyWebhook(Request $request): bool
    {
        // TODO Phase 3: Verify HMAC SHA256 signature từ MoMo
        // $secretKey = config('payment.momo.secret_key');
        throw new \RuntimeException('MoMo chưa được tích hợp.');
    }

    public function getTransactionStatus(string $transactionId): string
    {
        // TODO Phase 3: Gọi MoMo Query API
        throw new \RuntimeException('MoMo chưa được tích hợp.');
    }

    public function getProviderName(): string
    {
        return 'momo';
    }
}
