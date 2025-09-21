<?php

namespace App\Services;

use App\Models\{Cart, Orders, Order_items, Customers, Invoices};
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\ServiceProvision;
use App\Services\ProvisionService;
use App\Events\ProvisionCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService extends BaseService
{
    protected $provisionService;
    public function __construct()
    {
        $this->provisionService = app(ProvisionService::class);
    }
    /**
     * Create order and invoice from cart
     *
     * @param Cart $cart
     * @param int $customerId
     * @return array ['order' => Orders, 'invoice' => Invoices]
     * @throws Exception
     */
    // app/Services/OrderService.php

    // app/Services/OrderService.php

    public function createFromCart($cart, $customerId)
    {
        DB::beginTransaction();
        try {
            // Tạo order
            $order = \App\Models\Orders::create([
                'order_number' => 'ORD' . time(),
                'customer_id' => $customerId,
                'status' => 'pending',
                'subtotal' => $cart->subtotal,
                'total_amount' => $cart->total_amount,
                'created_by' => auth()->id()
            ]);

            // Lưu order items với thông tin chi tiết
            foreach ($cart->items as $cartItem) {
                $options = json_decode($cartItem->options, true) ?: [];

                Order_items::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'name' => $cartItem->name,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->unit_price,
                    'subtotal' => $cartItem->subtotal,
                    'total' => $cartItem->total,
                    'options' => $cartItem->options, // Giữ nguyên JSON
                    'duration' => $options['period'] ?? null,
                    'domain' => $options['domain'] ?? null
                ]);

                // Log để debug
                Log::info('Order item created with options', [
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'options' => $options
                ]);
            }

            // Tạo invoice
            $invoice = \App\Models\Invoices::create([
                'invoice_number' => 'INV' . time(),
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'status' => 'pending',
                'subtotal' => $order->subtotal,
                'total_amount' => $order->total_amount,
                'due_date' => \Carbon\Carbon::now()->addDays(7)
            ]);

            DB::commit();

            return ['order' => $order, 'invoice' => $invoice, 'success' => true];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Create order from cart failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createServiceProvision($orderItem, $options)
    {
        $serviceType = $options['service_type'] ?? null;

        if (!$serviceType) return;

        ServiceProvision::create([
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'customer_id' => $orderItem->order->customer_id,
            'provision_type' => $serviceType,
            'provision_status' => 'pending',
            'provision_data' => json_encode($options),
            'priority' => $this->getProvisionPriority($serviceType)
        ]);
    }

    private function getProvisionPriority($serviceType)
    {
        $priorities = [
            'domain' => 1,
            'ssl' => 2,
            'hosting' => 3,
            'vps' => 4,
            'email' => 5,
            'web_design' => 6,
            'advertising' => 7,
            'seo' => 8
        ];

        return $priorities[$serviceType] ?? 5;
    }

    /**
     * Create order record
     *
     * @param Cart $cart
     * @param Customers $customer
     * @return Orders
     */
    private function createOrder(Cart $cart, Customers $customer): Orders
    {
        return Orders::create([
            'order_number' => $this->generateOrderNumber(),
            'customer_id' => $customer->id,
            'status' => 'pending',
            'subtotal' => $cart->subtotal,
            'tax_amount' => $cart->tax_amount ?? 0,
            'discount_amount' => $cart->discount_amount ?? 0,
            'total_amount' => $cart->total_amount,
            'notes' => 'Order created from cart',
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Create order items from cart items
     *
     * @param Orders $order
     * @param Cart $cart
     */
    private function createOrderItems(Orders $order, Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $this->createOrderItem($order, $item);
        }
    }

    /**
     * Create single order item
     *
     * @param Orders $order
     * @param $cartItem
     * @return Order_items
     */
    private function createOrderItem(Orders $order, $cartItem): Order_items
    {
        // Parse options to get additional info
        $options = json_decode($cartItem->options, true) ?: [];
        $period = $options['period'] ?? 1;
        $domain = $options['domain'] ?? null;

        return Order_items::create([
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'name' => $cartItem->product->name,
            'sku' => $cartItem->product->sku ?? '',
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->unit_price,
            'tax_percent' => 0,
            'tax_amount' => $cartItem->tax_amount ?? 0,
            'discount_percent' => 0,
            'discount_amount' => $cartItem->discount_amount ?? 0,
            'subtotal' => $cartItem->subtotal,
            'total' => $cartItem->total,
            'options' => $cartItem->options,
            'duration' => $period,
            'domain' => $domain
        ]);
    }

    /**
     * Create invoice from order
     *
     * @param Orders $order
     * @return Invoices
     */
    private function createInvoice(Orders $order): Invoices
    {
        return Invoices::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'status' => 'draft',
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'notes' => 'Invoice for order ' . $order->order_number,
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Validate cart before processing
     *
     * @param Cart $cart
     * @throws Exception
     */
    private function validateCart(Cart $cart): void
    {
        if (!$cart || $cart->items->isEmpty()) {
            throw new Exception('Cart is empty or invalid');
        }

        // Check if cart is expired
        if ($cart->expires_at && $cart->expires_at->isPast()) {
            throw new Exception('Cart has expired');
        }

        // Validate each cart item
        foreach ($cart->items as $item) {
            if (!$item->product) {
                throw new Exception("Product not found for cart item {$item->id}");
            }

            if ($item->product->product_status !== 'active') {
                throw new Exception("Product {$item->product->name} is not available");
            }

            // Check stock if limited
            if ($item->product->stock >= 0 && $item->product->stock < $item->quantity) {
                throw new Exception("Insufficient stock for product {$item->product->name}");
            }
        }
    }

    /**
     * Validate customer
     *
     * @param int $customerId
     * @return Customers
     * @throws Exception
     */
    private function validateCustomer(int $customerId): Customers
    {
        $customer = Customers::find($customerId);

        if (!$customer) {
            throw new Exception('Customer not found');
        }

        if ($customer->status !== 'active') {
            throw new Exception('Customer account is not active');
        }

        return $customer;
    }

    /**
     * Generate unique order number
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        return $this->generateUniqueNumber('ORD');
    }

    /**
     * Generate unique invoice number
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        return $this->generateUniqueNumber('INV');
    }

    /**
     * Update order status
     *
     * @param Orders $order
     * @param string $status
     * @param string|null $notes
     * @return bool
     */
    public function updateStatus(Orders $order, string $status, ?string $notes = null): bool
    {
        return $this->transaction(function () use ($order, $status, $notes) {
            $updateData = ['status' => $status];

            if ($notes) {
                $updateData['notes'] = $order->notes . "\n" . $notes;
            }

            $updated = $order->update($updateData);

            if ($updated) {
                $this->logActivity('Order status updated', [
                    'order_id' => $order->id,
                    'old_status' => $order->getOriginal('status'),
                    'new_status' => $status,
                    'notes' => $notes
                ]);
            }

            return $updated;
        });
    }

    /**
     * Get order with full details
     *
     * @param int $orderId
     * @return Orders|null
     */
    public function getOrderWithDetails(int $orderId): ?Orders
    {
        return Orders::with([
            'customer.user',
            'items.product',
            'invoice',
            'payments'
        ])->find($orderId);
    }

    /**
     * Cancel order
     *
     * @param Orders $order
     * @param string $reason
     * @return bool
     * @throws Exception
     */
    public function cancelOrder(Orders $order, string $reason = ''): bool
    {
        if (!in_array($order->status, ['pending', 'processing'])) {
            throw new Exception('Cannot cancel order with status: ' . $order->status);
        }

        return $this->updateStatus($order, 'cancelled', "Order cancelled. Reason: {$reason}");
    }
    public function processCompletedOrder(Orders $order): array
    {
        return $this->transaction(function () use ($order) {
            $provisions = [];

            $this->logActivity('Processing completed order for provisions', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
                'total_items' => $order->items->count()
            ]);

            foreach ($order->items as $item) {
                if ($this->shouldCreateProvision($item)) {
                    $provision = $this->createProvisionForItem($item, $order);
                    $provisions[] = $provision;
                }
            }

            // Update order status if provisions created
            if (!empty($provisions)) {
                $order->update([
                    'status' => 'processing', // Đang xử lý provisions
                    'notes' => $order->notes . "\nProvisioning started - " . count($provisions) . " services"
                ]);
            }

            $this->logActivity('Order provisions created', [
                'order_id' => $order->id,
                'provisions_created' => count($provisions),
                'provision_ids' => array_map(fn($p) => $p->id, $provisions)
            ]);

            return $provisions;
        });
    }

    /**
     * Kiểm tra xem order item có cần tạo provision không
     */
    private function shouldCreateProvision(Order_items $item): bool
    {
        if (!$item->product) {
            return false;
        }

        // Chỉ tạo provision cho những loại sản phẩm này
        $provisionableTypes = ['ssl', 'domain', 'hosting', 'service'];

        return in_array($item->product->type, $provisionableTypes);
    }

    /**
     * Tạo provision cho order item
     */
    private function createProvisionForItem(Order_items $item, Orders $order): ServiceProvision
    {
        $options = json_decode($item->options, true) ?: [];

        // Xác định loại provision và priority
        $provisionType = $item->product->type;
        $priority = match ($provisionType) {
            'ssl' => 7,      // High priority
            'hosting' => 6,   // Medium-high 
            'domain' => 5,    // Normal
            default => 5
        };

        // Estimate completion time based on service type
        $estimatedCompletion = match ($provisionType) {
            'ssl' => now()->addHours(2),      // SSL cần 2h
            'hosting' => now()->addMinutes(30), // Hosting cần 30 phút
            'domain' => now()->addHours(24),   // Domain cần 1 ngày
            default => now()->addHours(24)
        };

        // Tạo provision data
        $provisionData = [
            'order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'customer_id' => $order->customer_id,
            'provision_type' => $provisionType,
            'provision_status' => 'pending',
            'provision_data' => [
                'domain' => $options['domain'] ?? null,
                'period' => $options['period'] ?? 1,
                'auto_renew' => $options['auto_renew'] ?? false,
                'created_from_order' => $order->order_number,
                'original_options' => $options
            ],
            'priority' => $priority,
            'estimated_completion' => $estimatedCompletion,
            'provision_notes' => "Auto-created from order {$order->order_number} - Item: {$item->name}",
        ];

        // Tạo provision record
        $provision = ServiceProvision::create($provisionData);

        // Fire event để gửi email notification
        event(new ProvisionCreated($provision));

        $this->logActivity('Provision created for order item', [
            'provision_id' => $provision->id,
            'order_id' => $order->id,
            'item_id' => $item->id,
            'provision_type' => $provisionType,
            'priority' => $priority,
            'estimated_completion' => $estimatedCompletion->toDateTimeString()
        ]);

        return $provision;
    }

    /**
     * Lấy tất cả provisions của một order
     */
    public function getOrderProvisions(Orders $order): array
    {
        $provisions = ServiceProvision::whereIn(
            'order_item_id',
            $order->items->pluck('id')
        )->with(['product', 'customer.user'])->get();

        return $provisions->toArray();
    }

    /**
     * Kiểm tra xem order có hoàn thành hết provisions chưa
     */
    public function isOrderFullyProvisioned(Orders $order): bool
    {
        $orderItemIds = $order->items->pluck('id');

        $totalProvisions = ServiceProvision::whereIn('order_item_id', $orderItemIds)->count();
        $completedProvisions = ServiceProvision::whereIn('order_item_id', $orderItemIds)
            ->where('provision_status', 'completed')->count();

        return $totalProvisions > 0 && $totalProvisions === $completedProvisions;
    }

    /**
     * Cập nhật order status khi tất cả provisions completed
     */
    public function updateOrderOnProvisionComplete(Orders $order): void
    {
        if ($this->isOrderFullyProvisioned($order)) {
            $order->update([
                'status' => 'completed',
                'notes' => $order->notes . "\nAll services provisioned successfully on " . now()->format('Y-m-d H:i:s')
            ]);

            $this->logActivity('Order fully provisioned', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'completion_time' => now()->toDateTimeString()
            ]);
        }
    }

    // SỬA LẠI method createFromCart để tích hợp provisions
    /**
     * Create order and invoice from cart - VERSION CÓ PROVISIONS
     */
    public function createFromCartWithProvisions(Cart $cart, int $customerId, bool $autoProcess = false): array
    {
        return $this->transaction(function () use ($cart, $customerId, $autoProcess) {
            // Tạo order và invoice như cũ
            $result = $this->createFromCart($cart, $customerId);

            if (!$result['success']) {
                return $result;
            }

            $order = $result['order'];
            $invoice = $result['invoice'];

            // Tạo provisions nếu được yêu cầu
            $provisions = [];
            if ($autoProcess) {
                $provisions = $this->processCompletedOrder($order);
            }

            $this->logActivity('Order with provisions created from cart', [
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $customerId,
                'total_amount' => $order->total_amount,
                'provisions_count' => count($provisions),
                'auto_process' => $autoProcess
            ]);

            return [
                'success' => true,
                'order' => $order,
                'invoice' => $invoice,
                'provisions' => $provisions
            ];
        });
    }
}
