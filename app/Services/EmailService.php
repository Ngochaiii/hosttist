<?php

namespace App\Services;

use App\Models\{Payments, Config};
use Illuminate\Support\Facades\{Mail, Log};
use Exception;

class EmailService extends BaseService
{
    /**
     * Send payment approved email to customer
     */
    public function sendPaymentApprovedEmail(Payments $payment): bool
    {
        $emailId = uniqid('email_approved_');
        
        Log::info("[{$emailId}] Starting payment approved email", [
            'payment_id' => $payment->id,
            'payment_number' => $payment->payment_number ?? 'N/A'
        ]);

        try {
            $user = $payment->order->customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                Log::error("[{$emailId}] Customer email not found", [
                    'payment_id' => $payment->id,
                    'has_user' => !is_null($user)
                ]);
                throw new Exception('Customer email not found');
            }

            // Prepare data for blade template
            $emailData = [
                'payment' => $payment,
                'user' => $user,
                'config' => $config,
                'invoice' => $payment->invoice,
                'order' => $payment->order,
                'verifiedDate' => $payment->verified_at ? 
                    $payment->verified_at->format('d/m/Y H:i:s') : 
                    now()->format('d/m/Y H:i:s'),
                'paymentMethodName' => $this->getPaymentMethodName($payment->payment_method)
            ];

            Log::debug("[{$emailId}] Sending payment approved email", [
                'recipient' => $user->email,
                'template' => 'emails.payment_approved'
            ]);

            // Send email using blade template
            Mail::send('emails.payment_approved', $emailData, function ($mail) use ($user, $payment) {
                $mail->to($user->email)
                    ->subject('Xác nhận thanh toán hóa đơn #' . ($payment->invoice->invoice_number ?? 'N/A'));
            });

            Log::info("[{$emailId}] Payment approved email sent successfully", [
                'payment_id' => $payment->id,
                'customer_email' => $user->email
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("[{$emailId}] Payment approved email failed", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Send payment rejected email to customer
     */
    public function sendPaymentRejectedEmail(Payments $payment, string $reason): bool
    {
        $emailId = uniqid('email_rejected_');
        
        Log::info("[{$emailId}] Starting payment rejected email", [
            'payment_id' => $payment->id,
            'reason' => $reason
        ]);

        try {
            $user = $payment->order->customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                Log::error("[{$emailId}] Customer email not found", [
                    'payment_id' => $payment->id
                ]);
                throw new Exception('Customer email not found');
            }

            $emailData = [
                'payment' => $payment,
                'user' => $user,
                'config' => $config,
                'reason' => $reason,
                'paymentMethodName' => $this->getPaymentMethodName($payment->payment_method)
            ];

            Mail::send('emails.payment_rejected', $emailData, function ($mail) use ($user, $payment) {
                $mail->to($user->email)
                    ->subject('Thông báo từ chối thanh toán #' . $payment->transaction_id);
            });

            Log::info("[{$emailId}] Payment rejected email sent successfully", [
                'payment_id' => $payment->id,
                'customer_email' => $user->email,
                'reason' => $reason
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("[{$emailId}] Payment rejected email failed", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send deposit request confirmation email
     */
    public function sendDepositRequestEmail($deposit, $paymentInfo = []): bool
    {
        $emailId = uniqid('email_deposit_');
        
        Log::info("[{$emailId}] Starting deposit request email", [
            'deposit_id' => $deposit->id ?? 'new',
            'transaction_code' => $deposit->transaction_code ?? 'N/A',
            'amount' => $deposit->amount
        ]);

        try {
            $user = $deposit->customer->user;

            if (!$user || !$user->email) {
                Log::error("[{$emailId}] Customer email not found", [
                    'deposit_id' => $deposit->id
                ]);
                throw new Exception('Customer email not found');
            }

            // Prepare deposit data cho blade template (dùng structure có sẵn)
            $depositData = [
                'customer_name' => $user->name,
                'transaction_code' => $deposit->transaction_code,
                'date' => $deposit->created_at->format('d/m/Y H:i:s'),
                'amount' => $deposit->amount,
                'payment_method' => $deposit->payment_method,
                'note_format' => $this->generateDepositNote($deposit),
                'bank_info' => $paymentInfo['bank_info'] ?? null,
                'momo_info' => $paymentInfo['momo_info'] ?? null,
                'zalopay_info' => $paymentInfo['zalopay_info'] ?? null,
                'qr_code_url' => $paymentInfo['qr_code_url'] ?? null,
            ];

            // Dùng blade template có sẵn
            Mail::send('emails.deposit_request', compact('depositData'), function ($mail) use ($user, $deposit) {
                $mail->to($user->email)
                    ->subject('Yêu cầu nạp tiền #' . $deposit->transaction_code);
            });

            Log::info("[{$emailId}] Deposit request email sent successfully", [
                'customer_email' => $user->email,
                'transaction_code' => $deposit->transaction_code
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("[{$emailId}] Deposit request email failed", [
                'deposit_id' => $deposit->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send admin notification for deposit request
     */
    public function sendAdminDepositNotification($deposit, string $adminEmail): bool
    {
        $emailId = uniqid('email_admin_deposit_');
        
        Log::info("[{$emailId}] Starting admin deposit notification", [
            'deposit_id' => $deposit->id,
            'admin_email' => $adminEmail,
            'amount' => $deposit->amount
        ]);

        try {
            $user = $deposit->customer->user;
            
            // Prepare data cho admin notification template có sẵn
            $depositData = [
                'customer_id' => $deposit->customer_id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'transaction_code' => $deposit->transaction_code,
                'date' => $deposit->created_at->format('d/m/Y H:i:s'),
                'amount' => $deposit->amount,
                'payment_method' => $deposit->payment_method,
                'note_format' => $this->generateDepositNote($deposit),
            ];

            // Dùng admin template có sẵn
            Mail::send('emails.admin_deposit_notification', compact('depositData'), function ($mail) use ($adminEmail, $deposit) {
                $mail->to($adminEmail)
                    ->subject('Thông báo yêu cầu nạp tiền mới #' . $deposit->transaction_code);
            });

            Log::info("[{$emailId}] Admin deposit notification sent successfully", [
                'admin_email' => $adminEmail,
                'transaction_code' => $deposit->transaction_code
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("[{$emailId}] Admin deposit notification failed", [
                'deposit_id' => $deposit->id,
                'admin_email' => $adminEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmationEmail($order): bool
    {
        $emailId = uniqid('email_order_');
        
        Log::info("[{$emailId}] Starting order confirmation email", [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id
        ]);

        try {
            $user = $order->customer->user;
            $config = Config::current();

            if (!$user || !$user->email) {
                Log::error("[{$emailId}] Customer email not found", [
                    'order_id' => $order->id
                ]);
                throw new Exception('Customer email not found');
            }

            $emailData = [
                'order' => $order,
                'user' => $user,
                'config' => $config,
                'orderStatusName' => $this->getOrderStatusName($order->status)
            ];

            Mail::send('emails.order_confirmation', $emailData, function ($mail) use ($user, $order) {
                $mail->to($user->email)
                    ->subject('Xác nhận đơn hàng #' . $order->order_number);
            });

            Log::info("[{$emailId}] Order confirmation email sent successfully", [
                'order_id' => $order->id,
                'customer_email' => $user->email
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("[{$emailId}] Order confirmation email failed", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Helper methods
     */
    private function generateDepositNote($deposit): string
    {
        return "NAP " . $deposit->transaction_code;
    }

    private function getPaymentMethodName(string $method): string
    {
        return match($method) {
            'bank' => 'Chuyển khoản ngân hàng',
            'wallet' => 'Thanh toán từ số dư tài khoản', 
            'momo' => 'Ví MoMo',
            'zalopay' => 'ZaloPay',
            'paypal' => 'PayPal',
            'crypto' => 'Tiền điện tử',
            'credit_card' => 'Thẻ tín dụng',
            'cash' => 'Tiền mặt',
            default => ucfirst($method)
        };
    }

    private function getOrderStatusName(string $status): string
    {
        return match($status) {
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý', 
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'shipped' => 'Đã giao hàng',
            'delivered' => 'Đã nhận hàng',
            default => ucfirst($status)
        };
    }
}