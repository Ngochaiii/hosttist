<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * VNPayGateway — tích hợp cổng thanh toán VNPay
 *
 * TODO (Phase 3): Fill implement khi có API key từ VNPay
 * Tài liệu: https://sandbox.vnpayment.vn/apis/
 *
 * Các thông số cần thêm vào config/payment.php:
 *   vnpay.tmn_code    — Terminal ID
 *   vnpay.hash_secret — Chuỗi bí mật để verify signature
 *   vnpay.url         — URL cổng thanh toán
 *   vnpay.return_url  — URL redirect sau khi thanh toán
 */
class VNPayGateway implements PaymentGatewayInterface
{
    public function createPaymentUrl(Orders $order): string
    {
        // TODO Phase 3: Build VNPay payment URL với signature
        // Tham khảo: vnp_TmnCode, vnp_Amount, vnp_ReturnUrl, vnp_SecureHash
        throw new \RuntimeException('VNPay chưa được tích hợp. Liên hệ admin để biết thêm.');
    }

    public function verifyWebhook(Request $request): bool
    {
        // TODO Phase 3: Verify vnp_SecureHash
        // $hashSecret = config('payment.vnpay.hash_secret');
        // Compare hash của các params với vnp_SecureHash trong request
        throw new \RuntimeException('VNPay chưa được tích hợp.');
    }

    public function getTransactionStatus(string $transactionId): string
    {
        // TODO Phase 3: Gọi VNPay Query API để check trạng thái
        throw new \RuntimeException('VNPay chưa được tích hợp.');
    }

    public function getProviderName(): string
    {
        return 'vnpay';
    }
}
