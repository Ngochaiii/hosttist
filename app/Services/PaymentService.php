<?php

namespace App\Services;

use App\Models\{Payments, Orders, Invoices, Customers, ServiceProvision};
use App\Services\{ProvisionService, EmailService};
use App\Services\Payment\GatewayFactory;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService extends BaseService
{
    protected $provisionService;
    protected $emailService;

    public function __construct(ProvisionService $provisionService, EmailService $emailService)
    {
        $this->provisionService = $provisionService;
        $this->emailService = $emailService;
    }

    /**
     * Approve payment and complete order process
     *
     * @param Payments $payment
     * @param int|null $verifiedBy
     * @return array
     * @throws Exception
     */
    // app/Services/PaymentService.php

    public function approvePayment($payment, $adminId)
    {
        DB::beginTransaction();
        try {
            // Lock payment row + double-check state để chống race-condition.
            $payment = Payments::where('id', $payment->id)->lockForUpdate()->first();
            if (!$payment) {
                DB::rollback();
                return ['success' => false, 'error' => 'Payment không tồn tại'];
            }
            if ($payment->status !== 'pending') {
                DB::rollback();
                return ['success' => false, 'error' => "Payment đã ở trạng thái '{$payment->status}', không thể duyệt lại"];
            }

            // Update payment status
            $payment->update([
                'status'      => 'completed',
                'verified_by' => $adminId,
                'verified_at' => now(),
            ]);

            // Update order status
            $order = $payment->order ?? $payment->invoice->order;
            $provisions = [];

            if ($order && $order->renewal_of_service_id) {
                // Đơn gia hạn → không tạo provision, chỉ extend CustomerService
                app(ServiceLifecycleService::class)->completeRenewalFromOrder($order);

                DB::commit();

                return [
                    'success'    => true,
                    'payment'    => $payment,
                    'provisions' => [],
                    'renewal'    => true,
                ];
            }

            if ($order) {
                $order->status = 'processing';
                $order->save();

                // Create service provisions
                foreach ($order->items as $item) {
                    $options = json_decode($item->options, true) ?: [];
                    $serviceType = $options['service_type'] ?? null;

                    if ($serviceType) {
                        $provision = \App\Models\ServiceProvision::create([
                            'order_item_id' => $item->id,
                            'product_id' => $item->product_id,
                            'customer_id' => $order->customer_id,
                            'provision_type' => $serviceType,
                            'provision_status' => 'pending',
                            'provision_data' => $item->options,
                            'priority' => $this->getPriority($serviceType)
                        ]);

                        $provisions[] = $provision;

                        Log::info('Service provision created', [
                            'provision_id' => $provision->id,
                            'type' => $serviceType,
                            'data' => $options
                        ]);
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'payment' => $payment,
                'provisions' => $provisions
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Approve payment failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Priority đồng bộ với OrderService (số càng cao = càng ưu tiên).
     * Tránh tình trạng OrderService rank ssl=7 còn PaymentService rank ssl=2 → xử lý sai thứ tự.
     */
    private function getPriority($serviceType)
    {
        $priorities = [
            'ssl'           => 7,
            'hosting'       => 6,
            'cloud_hosting' => 6,
            'vps'           => 6,
            'reseller'      => 6,
            'anti_ddos'     => 6,
            'domain'        => 5,
            'email'         => 5,
            'advertising'   => 4,
            'web_design'    => 3,
            'seo'           => 3,
        ];
        return $priorities[$serviceType] ?? 5;
    }

    /**
     * Reject payment with reason
     *
     * @param Payments $payment
     * @param string $reason
     * @param int|null $verifiedBy
     * @return array
     * @throws Exception
     */
    public function rejectPayment(Payments $payment, string $reason, ?int $verifiedBy = null): array
    {
        return $this->transaction(function () use ($payment, $reason, $verifiedBy) {
            // Lock + re-check để chống race với approve.
            $payment = Payments::where('id', $payment->id)->lockForUpdate()->first();
            if (!$payment) {
                throw new Exception('Payment không tồn tại');
            }
            if ($payment->status !== 'pending') {
                throw new Exception('Payment can only be rejected when status is pending');
            }

            // Update payment status
            $this->updatePaymentStatus($payment, 'failed', $verifiedBy, "Rejected: {$reason}");

            // Send rejection email
            $this->emailService->sendPaymentRejectedEmail($payment, $reason);

            $this->logActivity('Payment rejected', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'reason' => $reason,
                'verified_by' => $verifiedBy
            ]);

            return [
                'success' => true,
                'payment' => $payment->fresh()
            ];
        });
    }

    /**
     * Process wallet payment (auto-approve if sufficient balance)
     *
     * @param Orders $order
     * @param Customers $customer
     * @return array
     * @throws Exception
     */
    // Trong PaymentService - processWalletPayment() đã được fix
    public function processWalletPayment(Orders $order, Customers $customer): array
    {
        return $this->transaction(function () use ($order, $customer) {
            $amount = $order->total_amount;

            // Khóa row customer để check + trừ số dư atomic, tránh race condition 2 request song song
            $locked = Customers::where('id', $customer->id)->lockForUpdate()->first();

            if (!$locked || $locked->balance < $amount) {
                throw new Exception('Insufficient wallet balance');
            }

            $locked->decrement('balance', $amount);
            $customer->balance = $locked->fresh()->balance;

            $payment = $this->createPayment([
                'order_id' => $order->id,
                'invoice_id' => $order->invoice->id ?? null,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $amount,
                'payment_method' => 'wallet',
                'payment_date' => now(),
                'transaction_id' => $this->generateTransactionId('WALLET'),
                'status' => 'completed', // Completed luôn vì đã trừ tiền
                'notes' => 'Wallet payment - Auto approved'
            ]);

            $isRenewal = (bool) $order->renewal_of_service_id;

            // Invoice luôn paid (user đã trả tiền)
            if ($order->invoice) {
                $order->invoice->update(['status' => 'paid']);
            }

            if ($isRenewal) {
                // Đơn gia hạn: không tạo provision mới, chỉ extend CustomerService
                $order->update(['status' => 'completed']);
                app(ServiceLifecycleService::class)->completeRenewalFromOrder($order->fresh());
            } else {
                // Đơn mua mới: tạo pending provisions để admin điền credentials.
                // Order chỉ complete khi tất cả provision xong.
                $provisions = app(\App\Services\OrderService::class)
                    ->processCompletedOrder($order->fresh()->load('items.product'));

                if (empty($provisions)) {
                    // Không có item nào cần provision → order hoàn thành ngay
                    $order->update(['status' => 'completed']);
                }
                // processCompletedOrder đã tự set order.status = 'processing' khi có provision
            }

            // Gửi email + thông báo in-app xác nhận cho khách (nhánh wallet trước đây
            // không gửi gì cả). EmailService tự nuốt lỗi nên không rollback transaction.
            try {
                $this->emailService->sendPaymentApprovedEmail(
                    $payment->fresh()->load(['order.customer.user', 'order.items', 'invoice'])
                );
            } catch (\Exception $e) {
                Log::error('processWalletPayment: gửi thông báo thất bại', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            return [
                'success'     => true,
                'payment'     => $payment,
                'new_balance' => $customer->balance,
                'renewal'     => $isRenewal,
            ];
        });
    }

    /**
     * Create pending bank transfer payment
     *
     * @param Orders $order
     * @param array $bankDetails
     * @return array
     * @throws Exception
     */
    public function createBankTransferPayment(Orders $order, array $bankDetails = []): array
    {
        return $this->transaction(function () use ($order, $bankDetails) {
            $transactionCode = $this->generateTransactionId('PAY');

            $payment = $this->createPayment([
                'order_id' => $order->id,
                'invoice_id' => $order->invoice->id ?? null,
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $order->total_amount,
                'payment_method' => 'bank',
                'payment_date' => now(),
                'transaction_id' => $transactionCode,
                'status' => 'pending',
                'notes' => 'Bank transfer payment - Awaiting confirmation',
                'payment_details' => $bankDetails
            ]);

            // Update invoice status to sent
            if ($order->invoice && $order->invoice->status !== 'sent') {
                $order->invoice->update(['status' => 'sent']);
            }

            $this->logActivity('Bank transfer payment created', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'transaction_code' => $transactionCode
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'transaction_code' => $transactionCode
            ];
        });
    }

    /**
     * Create payment record
     *
     * @param array $paymentData
     * @return Payments
     */
    private function createPayment(array $paymentData): Payments
    {
        $this->validateRequired($paymentData, [
            'order_id',
            'amount',
            'payment_method',
            'status'
        ]);

        return Payments::create($paymentData);
    }

    /**
     * Update payment status with verification info
     *
     * @param Payments $payment
     * @param string $status
     * @param int|null $verifiedBy
     * @param string|null $notes
     */
    private function updatePaymentStatus(Payments $payment, string $status, ?int $verifiedBy = null, ?string $notes = null): void
    {
        $updateData = [
            'status' => $status,
            'verified_by' => $verifiedBy,
            'verified_at' => now()
        ];

        if ($notes) {
            $updateData['notes'] = $notes;
        }

        $payment->update($updateData);
    }

    /**
     * Validate payment can be approved
     *
     * @param Payments $payment
     * @throws Exception
     */
    private function validatePaymentForApproval(Payments $payment): void
    {
        if ($payment->status !== 'pending') {
            throw new Exception('Payment can only be approved when status is pending');
        }

        if (!$payment->order) {
            throw new Exception('Payment must have an associated order');
        }

        if ($payment->amount <= 0) {
            throw new Exception('Payment amount must be greater than zero');
        }
    }

    /**
     * Generate payment number
     *
     * @return string
     */
    private function generatePaymentNumber(): string
    {
        return $this->generateUniqueNumber('PAY');
    }

    /**
     * Generate transaction ID with prefix
     *
     * @param string $prefix
     * @return string
     */
    private function generateTransactionId(string $prefix): string
    {
        return $this->generateUniqueNumber($prefix);
    }

    /**
     * Get payment statistics
     *
     * @param array $filters
     * @return array
     */
    public function getPaymentStats(array $filters = []): array
    {
        $query = Payments::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return [
            'total_payments' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'pending_count' => $query->where('status', 'pending')->count(),
            'completed_count' => $query->where('status', 'completed')->count(),
            'failed_count' => $query->where('status', 'failed')->count(),
            'today_completed' => Payments::whereDate('payment_date', today())
                ->where('status', 'completed')
                ->sum('amount')
        ];
    }

    /**
     * Find payment by transaction ID
     *
     * @param string $transactionId
     * @return Payments|null
     */
    public function findByTransactionId(string $transactionId): ?Payments
    {
        return Payments::where('transaction_id', $transactionId)
            ->with(['order', 'invoice'])
            ->first();
    }

    /**
     * Xác nhận thanh toán từ payment gateway (dùng cho webhook / auto-approve).
     *
     * Được gọi bởi ProcessPaymentWebhook job sau khi nhận callback từ VNPay/MoMo/ZaloPay.
     * Khi Phase 3 hoàn thiện, các gateway thật sẽ gọi method này thông qua job.
     *
     * @param string $transactionId  — Transaction ID từ provider
     * @param string $provider       — vnpay | momo | zalopay | manual
     * @param array  $rawData        — Raw payload từ webhook
     * @return array
     */
    public function confirmPaymentFromGateway(string $transactionId, string $provider, array $rawData): array
    {
        $payment = $this->findByTransactionId($transactionId);

        if (!$payment) {
            Log::error('confirmPaymentFromGateway: không tìm thấy payment', [
                'transaction_id' => $transactionId,
                'provider'       => $provider,
            ]);
            return ['success' => false, 'error' => 'Payment not found', 'no_retry' => true];
        }

        // Fast-path idempotent: đã completed thì khỏi mở transaction/lock.
        // Provider thường gửi webhook trùng → nhánh này gánh phần lớn lưu lượng lặp.
        if ($payment->status === 'completed') {
            return ['success' => true, 'payment' => $payment, 'already_processed' => true];
        }

        return $this->transaction(function () use ($payment, $provider, $rawData) {
            // Lock row + re-check status TRONG transaction — đây mới là kiểm tra "thẩm quyền".
            // Hai webhook của cùng giao dịch đến đồng thời sẽ bị tuần tự hoá tại đây,
            // tránh tạo ServiceProvision 2 lần (double-provision).
            $payment = Payments::where('id', $payment->id)->lockForUpdate()->first();

            if ($payment->status === 'completed') {
                // Webhook đang chạy song song đã xử lý xong trước → bỏ qua (idempotent).
                return ['success' => true, 'payment' => $payment, 'already_processed' => true];
            }

            if ($payment->status !== 'pending') {
                return [
                    'success'  => false,
                    'error'    => "Payment status is '{$payment->status}', cannot confirm",
                    'no_retry' => true,
                ];
            }

            $order = $payment->order;

            // Cập nhật payment
            $payment->update([
                'status'      => 'completed',
                'verified_at' => now(),
                'notes'       => "Auto-confirmed via {$provider} gateway",
            ]);

            // Cập nhật order
            $order->update(['status' => 'processing']);

            // Cập nhật invoice
            if ($order->invoice) {
                $order->invoice->update(['status' => 'paid']);
            }

            // Tạo service provisions cho từng item
            $provisions = [];
            foreach ($order->items as $item) {
                $options     = json_decode($item->options, true) ?: [];
                $serviceType = $options['service_type'] ?? null;

                if ($serviceType) {
                    $provision = ServiceProvision::create([
                        'order_item_id'    => $item->id,
                        'product_id'       => $item->product_id,
                        'customer_id'      => $order->customer_id,
                        'provision_type'   => $serviceType,
                        'provision_status' => 'pending',
                        'provision_data'   => $item->options,
                        'priority'         => $this->getPriority($serviceType),
                    ]);

                    $provisions[] = $provision;
                }
            }

            // Gửi email xác nhận cho khách
            try {
                $this->emailService->sendPaymentApprovedEmail($payment->fresh());
            } catch (\Exception $e) {
                // Email lỗi không nên rollback cả transaction
                Log::error('confirmPaymentFromGateway: gửi email thất bại', [
                    'payment_id' => $payment->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            $this->logActivity('Payment confirmed from gateway', [
                'payment_id'     => $payment->id,
                'provider'       => $provider,
                'provisions'     => count($provisions),
            ]);

            return [
                'success'    => true,
                'payment'    => $payment->fresh(),
                'provisions' => $provisions,
            ];
        });
    }

    /**
     * Khởi tạo URL thanh toán qua gateway được chọn.
     * Dùng khi khách chọn phương thức VNPay/MoMo/ZaloPay.
     *
     * @param Orders $order
     * @param string $provider  — vnpay | momo | zalopay
     * @return string  URL để redirect khách hàng
     */
    public function createGatewayPaymentUrl(Orders $order, string $provider): string
    {
        $gateway = GatewayFactory::make($provider);
        return $gateway->createPaymentUrl($order);
    }
}
