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
    }

    public function showOrder($id)
    {
        try {
            $order = $this->orderService->getOrderWithDetails($id);

            if (!$order) {
                return redirect()->route('customer.orders')->with('error', 'Đơn hàng không tồn tại');
            }

            if (!Auth::user()->customer || Auth::user()->customer->id != $order->customer_id) {
                return redirect()->route('customer.orders')->with('error', 'Bạn không có quyền truy cập đơn hàng này');
            }

            $config = Config::current();

            return view('source.web.invoice.show_order', compact('order', 'config'));
        } catch (\Exception $e) {
            Log::error('Show order failed: ' . $e->getMessage(), ['order_id' => $id]);
            return redirect()->route('customer.orders')->with('error', 'Lỗi tải thông tin đơn hàng');
        }
    }

    public function cancelOrder($id, Request $request)
    {
        try {
            $order = $this->orderService->getOrderWithDetails($id);

            if (!$order) {
                return back()->with('error', 'Đơn hàng không tồn tại');
            }

            if (!Auth::user()->customer || Auth::user()->customer->id != $order->customer_id) {
                return back()->with('error', 'Bạn không có quyền hủy đơn hàng này');
            }

            if (!in_array($order->status, ['pending', 'processing'])) {
                return back()->with('error', 'Không thể hủy đơn hàng với trạng thái: ' . $order->status);
            }

            $success = $this->orderService->cancelOrder($order, $request->input('reason', 'Cancelled by customer'));

            return $success
                ? redirect()->route('customer.orders')->with('success', 'Đơn hàng đã được hủy thành công')
                : back()->with('error', 'Không thể hủy đơn hàng');
        } catch (\Exception $e) {
            Log::error('Cancel order failed: ' . $e->getMessage(), ['order_id' => $id]);
            return back()->with('error', 'Lỗi hủy đơn hàng: ' . $e->getMessage());
        }
    }

    public function updateStatus($id, Request $request)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:pending,processing,completed,cancelled,shipped,delivered',
                'notes'  => 'nullable|string|max:500',
            ]);

            $order = $this->orderService->getOrderWithDetails($id);

            if (!$order) {
                return back()->with('error', 'Đơn hàng không tồn tại');
            }

            $success = $this->orderService->updateStatus($order, $request->input('status'), $request->input('notes', ''));

            return $success
                ? back()->with('success', 'Trạng thái đơn hàng đã được cập nhật')
                : back()->with('error', 'Không thể cập nhật trạng thái đơn hàng');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Update order status failed: ' . $e->getMessage(), ['order_id' => $id]);
            return back()->with('error', 'Lỗi cập nhật trạng thái: ' . $e->getMessage());
        }
    }

    public function tracking($id)
    {
        try {
            $order = $this->orderService->getOrderWithDetails($id);

            if (!$order) {
                return redirect()->route('customer.orders')->with('error', 'Đơn hàng không tồn tại');
            }

            if (!Auth::user()->customer || Auth::user()->customer->id != $order->customer_id) {
                return redirect()->route('customer.orders')->with('error', 'Bạn không có quyền truy cập thông tin này');
            }

            return view('source.web.order.tracking', compact('order'));
        } catch (\Exception $e) {
            Log::error('Order tracking failed: ' . $e->getMessage(), ['order_id' => $id]);
            return redirect()->route('customer.orders')->with('error', 'Lỗi tải thông tin theo dõi');
        }
    }
}
