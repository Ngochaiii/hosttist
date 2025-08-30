<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Orders, Config};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\OrderService;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
        Log::info("[OrderController] Controller instantiated", [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'Guest',
            'service' => OrderService::class,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Show order details using OrderService
     */
    public function showOrder($id)
    {
        $requestId = uniqid('order_show_');
        Log::info("[{$requestId}] Order details requested", [
            'order_id' => $id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Guest',
            'customer_id' => Auth::user()->customer->id ?? null,
            'ip' => request()->ip()
        ]);

        try {
            // Use service to get order with full details
            $loadStart = microtime(true);
            $order = $this->orderService->getOrderWithDetails($id);
            $loadTime = microtime(true) - $loadStart;

            if (!$order) {
                Log::warning("[{$requestId}] Order not found", [
                    'order_id' => $id,
                    'user_id' => Auth::id()
                ]);
                return redirect()->route('customer.orders')
                    ->with('error', 'Đơn hàng không tồn tại');
            }

            Log::info("[{$requestId}] Order loaded successfully", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at->toDateTimeString(),
                'load_time_ms' => round($loadTime * 1000, 2),
                'relationships' => [
                    'has_customer' => !is_null($order->customer),
                    'has_items' => $order->items->count() > 0,
                    'items_count' => $order->items->count(),
                    'has_invoice' => !is_null($order->invoice),
                    'payments_count' => $order->payments->count()
                ]
            ]);

            // Check access permissions
            if (!Auth::user()->customer || Auth::user()->customer->id != $order->customer_id) {
                Log::warning("[{$requestId}] Unauthorized order access attempt", [
                    'user_customer_id' => Auth::user()->customer->id ?? 'null',
                    'order_customer_id' => $order->customer_id,
                    'order_id' => $order->id,
                    'user_id' => Auth::id()
                ]);

                return redirect()->route('customer.orders')
                    ->with('error', 'Bạn không có quyền truy cập đơn hàng này');
            }

            $config = Config::current();

            // Log order items details for debugging
            $itemsDetails = [];
            foreach ($order->items as $item) {
                $itemsDetails[] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                    'has_product' => !is_null($item->product),
                    'product_type' => $item->product->type ?? 'unknown',
                    'has_service' => !is_null($item->service_id)
                ];
            }

            Log::info("[{$requestId}] Order details access granted", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer->user->name ?? 'Unknown',
                'order_status' => $order->status,
                'items_details' => $itemsDetails,
                'config_loaded' => !is_null($config),
                'company_name' => $config->company_name ?? 'Unknown'
            ]);

            return view('source.web.invoice.show_order', compact('order', 'config'));

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Order details failed", [
                'order_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('customer.orders')
                ->with('error', 'Lỗi tải thông tin đơn hàng: ' . $e->getMessage());
        }
    }

    /**
     * Cancel order using OrderService
     */
    public function cancelOrder($id, Request $request)
    {
        $requestId = uniqid('order_cancel_');
        Log::info("[{$requestId}] Order cancellation requested", [
            'order_id' => $id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Guest',
            'reason' => $request->input('reason', 'No reason provided'),
            'ip' => $request->ip()
        ]);

        try {
            // Load order with details
            $loadStart = microtime(true);
            $order = $this->orderService->getOrderWithDetails($id);
            $loadTime = microtime(true) - $loadStart;

            if (!$order) {
                Log::warning("[{$requestId}] Cannot cancel: order not found", [
                    'order_id' => $id,
                    'user_id' => Auth::id()
                ]);
                return back()->with('error', 'Đơn hàng không tồn tại');
            }

            Log::debug("[{$requestId}] Order loaded for cancellation", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'current_status' => $order->status,
                'customer_id' => $order->customer_id,
                'total_amount' => $order->total_amount,
                'load_time_ms' => round($loadTime * 1000, 2)
            ]);

            // Check permissions
            if (!Auth::user()->customer || Auth::user()->customer->id != $order->customer_id) {
                Log::warning("[{$requestId}] Unauthorized cancellation attempt", [
                    'user_customer_id' => Auth::user()->customer->id ?? 'null',
                    'order_customer_id' => $order->customer_id,
                    'order_id' => $order->id
                ]);
                return back()->with('error', 'Bạn không có quyền hủy đơn hàng này');
            }

            // Check if order can be cancelled
            if (!in_array($order->status, ['pending', 'processing'])) {
                Log::warning("[{$requestId}] Cannot cancel order with current status", [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                    'allowed_statuses' => ['pending', 'processing']
                ]);
                return back()->with('error', 'Không thể hủy đơn hàng với trạng thái: ' . $order->status);
            }

            $reason = $request->input('reason', 'Cancelled by customer');
            
            Log::info("[{$requestId}] Processing order cancellation", [
                'order_id' => $order->id,
                'current_status' => $order->status,
                'cancellation_reason' => $reason
            ]);
            
            $cancelStart = microtime(true);
            $success = $this->orderService->cancelOrder($order, $reason);
            $cancelTime = microtime(true) - $cancelStart;

            if ($success) {
                Log::info("[{$requestId}] Order cancelled successfully", [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'reason' => $reason,
                    'cancelled_by' => Auth::id(),
                    'cancellation_time_ms' => round($cancelTime * 1000, 2)
                ]);

                return redirect()->route('customer.orders')
                    ->with('success', 'Đơn hàng đã được hủy thành công');
            } else {
                Log::error("[{$requestId}] Order cancellation failed (service returned false)", [
                    'order_id' => $order->id,
                    'reason' => $reason,
                    'cancellation_time_ms' => round($cancelTime * 1000, 2)
                ]);
                return back()->with('error', 'Không thể hủy đơn hàng');
            }

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Order cancellation exception", [
                'order_id' => $id,
                'reason' => $request->input('reason', 'No reason'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Lỗi hủy đơn hàng: ' . $e->getMessage());
        }
    }

    /**
     * Update order status (for admin use)
     */
    public function updateStatus($id, Request $request)
    {
        $requestId = uniqid('order_status_');
        Log::info("[{$requestId}] Order status update requested", [
            'order_id' => $id,
            'user_id' => Auth::id(),
            'new_status' => $request->input('status'),
            'notes' => $request->input('notes', ''),
            'ip' => $request->ip()
        ]);

        try {
            // Validate input
            $request->validate([
                'status' => 'required|string|in:pending,processing,completed,cancelled,shipped,delivered',
                'notes' => 'nullable|string|max:500'
            ]);

            $order = $this->orderService->getOrderWithDetails($id);
            if (!$order) {
                Log::warning("[{$requestId}] Order not found for status update", [
                    'order_id' => $id
                ]);
                return back()->with('error', 'Đơn hàng không tồn tại');
            }

            $newStatus = $request->input('status');
            $notes = $request->input('notes', '');
            
            Log::info("[{$requestId}] Updating order status", [
                'order_id' => $order->id,
                'old_status' => $order->status,
                'new_status' => $newStatus,
                'notes' => $notes
            ]);

            $updateStart = microtime(true);
            $success = $this->orderService->updateStatus($order, $newStatus, $notes);
            $updateTime = microtime(true) - $updateStart;

            if ($success) {
                Log::info("[{$requestId}] Order status updated successfully", [
                    'order_id' => $order->id,
                    'old_status' => $order->status,
                    'new_status' => $newStatus,
                    'update_time_ms' => round($updateTime * 1000, 2)
                ]);

                return back()->with('success', 'Trạng thái đơn hàng đã được cập nhật');
            } else {
                Log::error("[{$requestId}] Order status update failed", [
                    'order_id' => $order->id,
                    'new_status' => $newStatus
                ]);
                return back()->with('error', 'Không thể cập nhật trạng thái đơn hàng');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning("[{$requestId}] Order status update validation failed", [
                'order_id' => $id,
                'validation_errors' => $e->errors()
            ]);
            
            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Order status update exception", [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', 'Lỗi cập nhật trạng thái: ' . $e->getMessage());
        }
    }

    /**
     * Get order tracking information
     */
    public function tracking($id)
    {
        $requestId = uniqid('order_track_');
        Log::info("[{$requestId}] Order tracking requested", [
            'order_id' => $id,
            'user_id' => Auth::id(),
            'ip' => request()->ip()
        ]);

        try {
            $order = $this->orderService->getOrderWithDetails($id);
            
            if (!$order) {
                Log::warning("[{$requestId}] Order not found for tracking", [
                    'order_id' => $id
                ]);
                return redirect()->route('customer.orders')
                    ->with('error', 'Đơn hàng không tồn tại');
            }

            // Check permissions
            if (!Auth::user()->customer || Auth::user()->customer->id != $order->customer_id) {
                Log::warning("[{$requestId}] Unauthorized tracking access", [
                    'user_customer_id' => Auth::user()->customer->id ?? 'null',
                    'order_customer_id' => $order->customer_id
                ]);
                return redirect()->route('customer.orders')
                    ->with('error', 'Bạn không có quyền truy cập thông tin này');
            }

            Log::info("[{$requestId}] Order tracking loaded", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'tracking_available' => true
            ]);

            return view('source.web.order.tracking', compact('order'));

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Order tracking failed", [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect()->route('customer.orders')
                ->with('error', 'Lỗi tải thông tin theo dõi: ' . $e->getMessage());
        }
    }
}