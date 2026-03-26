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
    public function createFromCart(Cart $cart, int $customerId): array
    {
        DB::beginTransaction();
        try {
            $order = Orders::create([
                'order_number' => $this->generateUniqueNumber('ORD'),
                'customer_id' => $customerId,
                'status' => 'pending',
                'subtotal' => $cart->subtotal,
                'total_amount' => $cart->total_amount,
                'created_by' => Auth::id()
            ]);

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
                    'options' => $cartItem->options,
                    'duration' => $options['period'] ?? null,
                    'domain' => $options['domain'] ?? null
                ]);
            }

            $invoice = Invoices::create([
                'invoice_number' => $this->generateUniqueNumber('INV'),
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'status' => 'pending',
                'subtotal' => $order->subtotal,
                'total_amount' => $order->total_amount,
                'due_date' => Carbon::now()->addDays(7)
            ]);

            DB::commit();

            return ['order' => $order, 'invoice' => $invoice, 'success' => true];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Create order from cart failed: ' . $e->getMessage());
            throw $e;
        }
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

}
