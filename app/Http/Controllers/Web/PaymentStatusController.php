<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Payments;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentStatusController extends Controller
{
    /**
     * Trang chờ xác nhận thanh toán (bank transfer / gateway redirect về)
     */
    public function pending(int $id)
    {
        $payment = $this->getPaymentForUser($id);

        if ($payment->status === 'completed') {
            return redirect()->route('payment.success', $id);
        }

        if ($payment->status === 'failed') {
            return redirect()->route('payment.failed', $id);
        }

        return view('source.web.payment.pending', compact('payment'));
    }

    /**
     * Trang thanh toán thành công
     */
    public function success(int $id)
    {
        $payment = $this->getPaymentForUser($id);

        if ($payment->status !== 'completed') {
            return redirect()->route('payment.pending', $id);
        }

        return view('source.web.payment.success', compact('payment'));
    }

    /**
     * Trang thanh toán thất bại
     */
    public function failed(int $id)
    {
        $payment = $this->getPaymentForUser($id);

        return view('source.web.payment.failed', compact('payment'));
    }

    /**
     * API endpoint polling trạng thái thanh toán (AJAX từ trang pending)
     */
    public function checkStatus(int $id): JsonResponse
    {
        $payment = $this->getPaymentForUser($id);

        return response()->json([
            'status'       => $payment->status,
            'redirect_url' => match ($payment->status) {
                'completed' => route('payment.success', $id),
                'failed'    => route('payment.failed', $id),
                default     => null,
            },
        ]);
    }

    private function getPaymentForUser(int $id): Payments
    {
        $payment = Payments::with(['order.customer.user', 'order.invoice'])
            ->findOrFail($id);

        // Chỉ cho xem payment của chính mình
        if ($payment->order->customer->user_id !== Auth::id()) {
            abort(403);
        }

        return $payment;
    }
}
