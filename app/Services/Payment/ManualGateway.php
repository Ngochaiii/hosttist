<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Orders;
use App\Models\Payments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ManualGateway — chuyển khoản ngân hàng thủ công
 * Admin xác nhận bằng tay, không có redirect hay webhook thật.
 * Đây là fallback khi chưa có API gateway nào được cấu hình.
 */
class ManualGateway implements PaymentGatewayInterface
{
    public function createPaymentUrl(Orders $order): string
    {
        // Manual gateway không có URL redirect — trả về trang hướng dẫn chuyển khoản
        return route('payment.pending', ['id' => $order->id]);
    }

    public function verifyWebhook(Request $request): bool
    {
        // Manual gateway không có webhook — admin duyệt tay qua admin panel
        // Method này không bao giờ được gọi với ManualGateway
        return false;
    }

    public function getTransactionStatus(string $transactionId): string
    {
        $payment = Payments::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return 'not_found';
        }

        return match ($payment->status) {
            'completed' => 'success',
            'failed'    => 'failed',
            'pending'   => 'pending',
            default     => 'unknown',
        };
    }

    public function getProviderName(): string
    {
        return 'manual';
    }
}
