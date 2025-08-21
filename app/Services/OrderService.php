<?php

namespace App\Services;

use App\Models\{Cart, Orders, Order_items, Customers, Invoices};
use Illuminate\Support\Facades\Auth;
use Exception;

class OrderService extends BaseService
{
    /**
     * Create order and invoice from cart
     *
     * @param Cart $cart
     * @param int $customerId
     * @return array ['order' => Orders, 'invoice' => Invoices]
     * @throws Exception
     */
    public function createFromCart(Cart $cart, int $customerId): array
    {
        return $this->transaction(function() use ($cart, $customerId) {
            // Validate cart
            $this->validateCart($cart);
            
            // Validate customer
            $customer = $this->validateCustomer($customerId);
            
            // Create order
            $order = $this->createOrder($cart, $customer);
            
            // Create order items
            $this->createOrderItems($order, $cart);
            
            // Create invoice
            $invoice = $this->createInvoice($order);
            
            $this->logActivity('Order created from cart', [
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $customerId,
                'total_amount' => $order->total_amount
            ]);
            
            return [
                'success' => true,
                'order' => $order,
                'invoice' => $invoice
            ];
        });
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
        return $this->transaction(function() use ($order, $status, $notes) {
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
}