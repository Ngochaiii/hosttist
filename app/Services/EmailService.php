<?php

namespace App\Services;

use App\Models\Payments;
use App\Notifications\CustomerAlert;
use Illuminate\Support\Facades\Log;

/**
 * Hub thông báo cho khách. Luồng gửi email đã được gỡ bỏ —
 * mọi thông báo đi qua kênh in-app (database notification).
 */
class EmailService extends BaseService
{
    /**
     * Thông báo in-app cho khách khi thanh toán được duyệt.
     */
    public function notifyPaymentApproved(Payments $payment): bool
    {
        $context = uniqid('notify_approved_');

        $user = $payment->order->customer->user ?? null;
        if (!$user) {
            Log::error("[{$context}] Customer user not found", [
                'payment_id' => $payment->id,
            ]);
            return false;
        }

        return $this->notifyInApp($user, new CustomerAlert(
            'payment_approved',
            'Thanh toán đã được xác nhận',
            'Hóa đơn #' . ($payment->invoice->invoice_number ?? $payment->payment_number ?? $payment->id)
                . ' đã được thanh toán thành công. Dịch vụ của bạn đang được kích hoạt.',
            route('customer.invoices'),
            'success'
        ), $context);
    }

    /**
     * Thông báo in-app cho khách khi thanh toán bị từ chối.
     */
    public function notifyPaymentRejected(Payments $payment, string $reason): bool
    {
        $context = uniqid('notify_rejected_');

        $user = $payment->order->customer->user ?? null;
        if (!$user) {
            Log::error("[{$context}] Customer user not found", [
                'payment_id' => $payment->id,
            ]);
            return false;
        }

        return $this->notifyInApp($user, new CustomerAlert(
            'payment_rejected',
            'Thanh toán bị từ chối',
            'Thanh toán #' . ($payment->transaction_id ?? $payment->id) . ' đã bị từ chối. Lý do: ' . $reason,
            route('customer.invoices'),
            'danger'
        ), $context);
    }

    /**
     * Gửi thông báo in-app (kênh database). Không bao giờ throw —
     * thông báo lỗi không được phép chặn luồng nghiệp vụ.
     */
    public function notifyInApp($user, CustomerAlert $alert, string $context = ''): bool
    {
        try {
            $user->notify($alert);
            return true;
        } catch (\Throwable $e) {
            Log::error("[{$context}] In-app notification failed", [
                'user_id' => $user->id ?? null,
                'type'    => $alert->type,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
