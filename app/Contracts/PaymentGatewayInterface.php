<?php

namespace App\Contracts;

use App\Models\Orders;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Tạo URL thanh toán để redirect khách hàng
     */
    public function createPaymentUrl(Orders $order): string;

    /**
     * Xác minh chữ ký webhook từ provider
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Lấy trạng thái giao dịch từ provider
     */
    public function getTransactionStatus(string $transactionId): string;

    /**
     * Tên provider (vnpay, momo, zalopay, manual)
     */
    public function getProviderName(): string;
}
