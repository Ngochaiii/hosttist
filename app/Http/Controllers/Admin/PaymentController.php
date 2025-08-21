<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments;
use App\Models\Invoices;
use App\Models\Orders;
use App\Models\Customers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Config;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    /**
     * Hiển thị danh sách yêu cầu thanh toán
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $payments = Payments::with(['invoice', 'order.customer.user'])
            ->when($status, function ($query, $status) {
                if ($status !== 'all') {
                    return $query->where('status', $status);
                }
            })
            ->latest()
            ->paginate(10);

        $counts = [
            'all' => Payments::count(),
            'pending' => Payments::where('status', 'pending')->count(),
            'completed' => Payments::where('status', 'completed')->count(),
            'failed' => Payments::where('status', 'failed')->count(),
        ];

        // Thêm dữ liệu thống kê
        $stats = [
            'today_payments' => Payments::whereDate('created_at', Carbon::today())
                ->where('status', 'completed')
                ->sum('amount'),
            'total_completed' => Payments::where('status', 'completed')->sum('amount'),
            'total_pending' => Payments::where('status', 'pending')->sum('amount'),
        ];

        return view('source.admin.payments.index', compact('payments', 'status', 'counts', 'stats'));
    }

    public function approve(Request $request, $id)
    {
        $payment = Payments::with(['invoice', 'order.customer.user'])->findOrFail($id);

        try {
            $result = $this->paymentService->approvePayment($payment, Auth::id());

            if ($result['success']) {
                return redirect()->route('admin.payments.index')
                    ->with('success', 'Thanh toán đã được xác nhận và dịch vụ đã được cung cấp.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi xử lý thanh toán: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        $payment = Payments::findOrFail($id);

        try {
            $result = $this->paymentService->rejectPayment(
                $payment,
                $request->reason,
                Auth::id()
            );

            return redirect()->route('admin.payments.index')
                ->with('success', 'Thanh toán đã bị từ chối.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

}
