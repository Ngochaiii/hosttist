<?php

namespace App\Services;

use App\Models\{CustomerService, ServiceProvision, Customers, Orders, Order_items, Invoices};
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServiceLifecycleService extends BaseService
{
    public const RENEWAL_VAT_RATE = 0.10;

    public function __construct(
        private PaymentService $paymentService,
        private EmailService   $emailService
    ) {}

    /**
     * Kích hoạt CustomerService khi provision hoàn thành.
     * Gọi từ ProvisionService::markProvisionCompleted().
     */
    public function activateFromProvision(ServiceProvision $provision): CustomerService
    {
        // Domain có vòng đời riêng (hạn theo năm, giá gia hạn = renew_price) → tách xử lý.
        $pdata = is_array($provision->provision_data)
            ? $provision->provision_data
            : (json_decode($provision->provision_data ?? '', true) ?: []);

        $domainSvc = app(DomainProvisioningService::class);
        if ($domainSvc->isDomainProvision($provision, $pdata)) {
            return $domainSvc->activate($provision, $pdata);
        }

        return $this->transaction(function () use ($provision) {
            $product  = $provision->product;
            $orderItem = $provision->orderItem;

            // Tính ngày hết hạn dựa trên chu kỳ sản phẩm
            $billingCycle  = $this->detectBillingCycle($product);
            $expiresAt     = $this->calculateExpiryDate($billingCycle);
            $leadDays      = (int) config('services.renewal.reminder_lead_days', 7);
            $renewalDate   = $expiresAt->copy()->subDays($leadDays);

            // auto_renew có thể được user chọn lúc mua và lưu trong provision_data
            $provisionData = is_array($provision->provision_data)
                ? $provision->provision_data
                : (json_decode($provision->provision_data ?? '', true) ?: []);
            $autoRenew = (bool) ($provisionData['auto_renew'] ?? false);

            // Lock-in renewal price từ giá mua thực tế (order_item), fallback sang product.price.
            // Phải > 0 — nếu cả 2 nguồn đều invalid thì throw, không tạo CS với giá 0
            // (sẽ làm renew() luôn fail và customer không hiểu vì sao).
            $renewalPrice = (float) ($orderItem?->unit_price ?? $product->price ?? 0);
            if ($renewalPrice <= 0) {
                throw new Exception(sprintf(
                    'Không thể activate CustomerService: renewal_price không hợp lệ (provision #%d, product #%d, order_item #%d, price=%s).',
                    $provision->id,
                    $provision->product_id,
                    $provision->order_item_id ?? 0,
                    var_export($renewalPrice, true)
                ));
            }

            $service = CustomerService::create([
                'customer_id'       => $provision->customer_id,
                'provision_id'      => $provision->id,
                'product_id'        => $provision->product_id,
                'order_item_id'     => $provision->order_item_id,
                'status'            => 'active',
                'started_at'        => now(),
                'expires_at'        => $expiresAt,
                'next_renewal_date' => $renewalDate,
                'auto_renew'        => $autoRenew,
                'renewal_price'     => $renewalPrice,
                'renewal_price_locked_at' => now(),
                'billing_cycle'     => $billingCycle,
            ]);

            $this->logActivity('CustomerService activated', [
                'customer_service_id' => $service->id,
                'provision_id'        => $provision->id,
                'expires_at'          => $expiresAt->toDateString(),
            ]);

            return $service;
        });
    }

    /**
     * Giá gia hạn HIỆN TẠI cho 1 dịch vụ.
     *
     * Chính sách: theo giá hiện tại của product (admin tăng giá → khách cũ trả giá mới khi gia hạn,
     * phản ánh chi phí vận hành/lạm phát). Ưu tiên sale_price nếu admin đang chạy KM.
     *
     * Fallback: nếu product đã bị xoá/disable (price <= 0), dùng renewal_price snapshot lần trước —
     * đảm bảo dịch vụ vẫn gia hạn được, không bị "treo" do admin xoá nhầm product.
     *
     * @return float|null  Giá > 0, hoặc null nếu không xác định được giá.
     */
    public function currentRenewalPrice(CustomerService $service): ?float
    {
        $product = $service->product;
        if ($product) {
            $live = (float) (($product->sale_price > 0) ? $product->sale_price : $product->price);
            if ($live > 0) {
                return $live;
            }
        }
        $snapshot = (float) ($service->renewal_price ?? 0);
        return $snapshot > 0 ? $snapshot : null;
    }

    /**
     * Gia hạn dịch vụ — tạo order mới nếu cần thanh toán, hoặc tự trừ ví.
     */
    public function renew(CustomerService $service, Customers $customer): array
    {
        if (!in_array($service->status, ['active', 'expired'])) {
            return ['success' => false, 'error' => 'Dịch vụ không thể gia hạn từ trạng thái hiện tại.'];
        }

        $amount = $this->currentRenewalPrice($service);
        if (!$amount) {
            return ['success' => false, 'error' => 'Giá gia hạn chưa được cấu hình. Liên hệ admin.'];
        }

        return $this->transaction(function () use ($service, $customer, $amount) {
            if (!$customer->hasBalance($amount)) {
                return [
                    'success'         => false,
                    'error'           => 'Số dư ví không đủ để gia hạn.',
                    'need_deposit'    => true,
                    'required_amount' => $amount,
                ];
            }

            $previousPrice = (float) ($service->renewal_price ?? 0);

            // Trừ ví và gia hạn
            $customer->decrement('balance', $amount);

            $newExpiry = $this->extendExpiry($service);

            $service->update([
                'status'             => 'active',
                'expires_at'         => $newExpiry,
                'next_renewal_date'  => $newExpiry->copy()->subDays((int) config('services.renewal.reminder_lead_days', 7)),
                // Snapshot giá vừa thu để hiển thị "lần thanh toán gần nhất" + làm fallback
                // nếu product bị xoá sau này.
                'renewal_price'           => $amount,
                'renewal_price_locked_at' => now(),
                // Reset notification flags để nhắc lại chu kỳ mới
                'notified_30d_at'    => null,
                'notified_15d_at'    => null,
                'notified_7d_at'     => null,
                'notified_1d_at'     => null,
            ]);

            // Đồng bộ Domain (nếu CS này là tên miền).
            app(DomainProvisioningService::class)->syncFromService($service);

            $this->logActivity('CustomerService renewed', [
                'customer_service_id' => $service->id,
                'new_expiry'          => $newExpiry->toDateString(),
                'amount_charged'      => $amount,
                'previous_price'      => $previousPrice,
                'price_changed'       => $previousPrice > 0 && abs($previousPrice - $amount) > 0.01,
            ]);

            return [
                'success'     => true,
                'new_expiry'  => $newExpiry,
                'new_balance' => $customer->fresh()->balance,
            ];
        });
    }

    /**
     * Hủy dịch vụ theo yêu cầu khách.
     */
    public function cancel(CustomerService $service, string $reason = ''): bool
    {
        return $this->transaction(function () use ($service, $reason) {
            $service->update([
                'status' => 'cancelled',
                'notes'  => $reason ?: $service->notes,
            ]);

            $this->logActivity('CustomerService cancelled', [
                'customer_service_id' => $service->id,
                'reason'              => $reason,
            ]);

            return true;
        });
    }

    /**
     * Mark service as expired (gọi bởi CheckServiceExpiry command).
     */
    public function markExpired(CustomerService $service): void
    {
        $service->update(['status' => 'expired']);

        // Đồng bộ Domain (nếu CS này là tên miền).
        app(DomainProvisioningService::class)->syncFromService($service);

        $this->logActivity('CustomerService expired', [
            'customer_service_id' => $service->id,
        ]);
    }

    /**
     * Auto-renew: chỉ áp dụng khi auto_renew = true và đủ số dư.
     * Gọi bởi CheckServiceExpiry command.
     */
    public function attemptAutoRenew(CustomerService $service): bool
    {
        if (!$service->auto_renew) {
            return false;
        }

        $customer = $service->customer;
        $result   = $this->renew($service, $customer);

        if ($result['success']) {
            Log::info('Auto-renew thành công', [
                'customer_service_id' => $service->id,
                'customer_id'         => $customer->id,
            ]);
            return true;
        }

        Log::warning('Auto-renew thất bại (số dư không đủ)', [
            'customer_service_id' => $service->id,
            'required'            => $this->currentRenewalPrice($service),
            'balance'             => $customer->balance,
        ]);
        return false;
    }

    /**
     * Tính số tiền cho 1 lần gia hạn (subtotal/VAT/total).
     * Subtotal = giá CURRENT của product (không phải snapshot trên CS).
     */
    public function computeRenewalAmounts(CustomerService $service, bool $vatInvoice = false): array
    {
        $subtotal      = (float) ($this->currentRenewalPrice($service) ?? 0);
        $previousPrice = (float) ($service->renewal_price ?? 0);
        $vatRate       = $vatInvoice ? self::RENEWAL_VAT_RATE : 0.0;
        $vatAmount     = round($subtotal * $vatRate);
        $total         = $subtotal + $vatAmount;

        return [
            'subtotal'      => $subtotal,
            'vatInvoice'    => $vatInvoice,
            'vatRate'       => $vatRate,
            'vatAmount'     => $vatAmount,
            'total'         => $total,
            // Phục vụ UI hiển thị "đợt trước trả X, lần này Y" để khách thấy minh bạch.
            'previousPrice' => $previousPrice,
            'priceChanged'  => $previousPrice > 0 && abs($previousPrice - $subtotal) > 0.01,
        ];
    }

    /**
     * Tạo Order + OrderItem + Invoice cho lần gia hạn.
     * Trả về ['order' => Orders, 'invoice' => Invoices].
     */
    public function createRenewalOrder(
        CustomerService $service,
        Customers $customer,
        bool $vatInvoice = false,
        array $vatInfo = []
    ): array {
        if (!$this->currentRenewalPrice($service)) {
            throw new Exception('Giá gia hạn chưa được cấu hình cho dịch vụ này.');
        }

        return $this->transaction(function () use ($service, $customer, $vatInvoice, $vatInfo) {
            $amounts = $this->computeRenewalAmounts($service, $vatInvoice);

            $order = Orders::create([
                'order_number'         => $this->generateUniqueNumber('REN'),
                'customer_id'          => $customer->id,
                'status'               => 'pending',
                'subtotal'             => $amounts['subtotal'],
                'tax_amount'           => $amounts['vatAmount'],
                'discount_amount'      => 0,
                'total_amount'         => $amounts['total'],
                'notes'                => 'Gia hạn dịch vụ #' . $service->id,
                'created_by'           => Auth::id(),
                'renewal_of_service_id' => $service->id,
            ]);

            $product      = $service->product;
            $durationMonths = match ($service->billing_cycle) {
                'monthly' => 1,
                'yearly'  => 12,
                default   => null,
            };
            Order_items::create([
                'order_id'    => $order->id,
                'product_id'  => $service->product_id,
                'name'        => ($product->name ?? 'Dịch vụ #' . $service->id) . ' (Gia hạn)',
                'quantity'    => 1,
                'price'       => $amounts['subtotal'],
                'subtotal'    => $amounts['subtotal'],
                'total'       => $amounts['total'],
                'options'     => json_encode(['renewal' => true, 'customer_service_id' => $service->id]),
                'duration'    => $durationMonths,
            ]);

            $invoice = Invoices::create([
                'invoice_number'        => $this->generateUniqueNumber('INV'),
                'order_id'              => $order->id,
                'customer_id'           => $customer->id,
                'status'                => 'pending',
                'subtotal'              => $amounts['subtotal'],
                'tax_amount'            => $amounts['vatAmount'],
                'discount_amount'       => 0,
                'total_amount'          => $amounts['total'],
                'due_date'              => Carbon::now()->addDays(7),
                'notes'                 => 'Hoá đơn gia hạn dịch vụ #' . $service->id,
                'vat_invoice_requested' => $vatInvoice,
                'vat_company_name'      => $vatInfo['vat_company_name']    ?? null,
                'vat_tax_code'          => $vatInfo['vat_tax_code']        ?? null,
                'vat_company_address'   => $vatInfo['vat_company_address'] ?? null,
                'vat_company_email'     => $vatInfo['vat_company_email']   ?? null,
            ]);

            $this->logActivity('Renewal order created', [
                'order_id'            => $order->id,
                'invoice_id'          => $invoice->id,
                'customer_service_id' => $service->id,
                'total'               => $amounts['total'],
            ]);

            return ['order' => $order, 'invoice' => $invoice];
        });
    }

    /**
     * Khi admin duyệt payment cho renewal order → gia hạn CustomerService.
     * Gọi bởi PaymentService::approvePayment() cho order có renewal_of_service_id.
     */
    public function completeRenewalFromOrder(Orders $order): array
    {
        if (!$order->renewal_of_service_id) {
            throw new Exception('Order không phải là đơn gia hạn.');
        }

        $service = CustomerService::find($order->renewal_of_service_id);
        if (!$service) {
            throw new Exception('Không tìm thấy dịch vụ cần gia hạn.');
        }

        return $this->transaction(function () use ($service, $order) {
            $newExpiry = $this->extendExpiry($service);

            // Snapshot giá thực thu (subtotal của order — đã loại VAT) làm "last paid".
            $paidSubtotal = (float) ($order->subtotal ?? $order->total_amount ?? 0);

            $service->update([
                'status'            => 'active',
                'expires_at'        => $newExpiry,
                'next_renewal_date' => $newExpiry->copy()->subDays((int) config('services.renewal.reminder_lead_days', 7)),
                'renewal_price'           => $paidSubtotal > 0 ? $paidSubtotal : $service->renewal_price,
                'renewal_price_locked_at' => $paidSubtotal > 0 ? now() : $service->renewal_price_locked_at,
                'notified_30d_at'   => null,
                'notified_15d_at'   => null,
                'notified_7d_at'    => null,
                'notified_1d_at'    => null,
            ]);

            // Đồng bộ Domain (nếu CS này là tên miền).
            app(DomainProvisioningService::class)->syncFromService($service);

            // Đồng bộ lại legacy Products record (nếu CS được backfill từ Products cũ)
            // để UI cũ hiển thị ngày hết hạn mới nhất.
            if ($service->legacy_product_id) {
                \App\Models\Products::where('id', $service->legacy_product_id)->update([
                    'end_date'       => $newExpiry,
                    'next_due_date'  => $newExpiry,
                    'service_status' => 'active',
                ]);
            }

            $order->update(['status' => 'completed']);
            if ($order->invoice) {
                $order->invoice->update(['status' => 'paid']);
            }

            $this->logActivity('CustomerService renewed via bank payment', [
                'customer_service_id' => $service->id,
                'order_id'            => $order->id,
                'new_expiry'          => $newExpiry->toDateString(),
            ]);

            return ['success' => true, 'new_expiry' => $newExpiry, 'service' => $service->fresh()];
        });
    }

    // ===== Private helpers =====

    private function detectBillingCycle($product): string
    {
        // Dựa vào recurring_period hoặc tên sản phẩm
        if (isset($product->recurring_period)) {
            return $product->recurring_period >= 12 ? 'yearly' : 'monthly';
        }
        return 'yearly';
    }

    private function calculateExpiryDate(string $cycle): Carbon
    {
        return match ($cycle) {
            'monthly' => now()->addMonth(),
            'yearly'  => now()->addYear(),
            default   => now()->addYear(),
        };
    }

    private function extendExpiry(CustomerService $service): Carbon
    {
        $base = $service->expires_at && $service->expires_at->isFuture()
            ? $service->expires_at
            : now();

        return match ($service->billing_cycle) {
            'monthly' => $base->copy()->addMonth(),
            'yearly'  => $base->copy()->addYear(),
            default   => $base->copy()->addYear(),
        };
    }
}
