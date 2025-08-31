<?php

namespace App\Services;

use App\Models\{Payments, Orders, Invoices, Customers};
use App\Services\{ProvisionService, EmailService};
use Illuminate\Support\Facades\Auth;
use Exception;

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
    public function approvePayment(Payments $payment, ?int $verifiedBy = null): array
    {
        return $this->transaction(function () use ($payment, $verifiedBy) {
            // Validate payment
            $this->validatePaymentForApproval($payment);

            // Update payment status
            $this->updatePaymentStatus($payment, 'completed', $verifiedBy);

            // Update invoice status
            if ($payment->invoice) {
                $payment->invoice->update(['status' => 'paid']);
            }

            // Update order status and provision services
            if ($payment->order) {
                $payment->order->update(['status' => 'completed']);

                // Create provisions for service products
                $provisionResults = $this->provisionService->createFromOrder($payment->order);
            }

            // Send confirmation email
            $this->emailService->sendPaymentApprovedEmail($payment);

            $this->logActivity('Payment approved', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'verified_by' => $verifiedBy
            ]);

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'provisions' => $provisionResults ?? []
            ];
        });
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
            // Validate payment
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

            // Kiểm tra số dư - nếu không đủ thì báo lỗi để redirect nạp tiền
            if (!$customer->hasBalance($amount)) {
                throw new Exception('Insufficient wallet balance');
            }

            // Trừ tiền từ ví và tạo payment completed luôn
            $customer->decrement('balance', $amount);

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

            // Update order và invoice
            $order->update(['status' => 'completed']);
            if ($order->invoice) {
                $order->invoice->update(['status' => 'paid']);
            }

            // Provision services
            $provisionResults = $this->provisionService->createFromOrder($order);

            return [
                'success' => true,
                'payment' => $payment,
                'new_balance' => $customer->fresh()->balance,
                'provisions' => $provisionResults ?? []
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
}
