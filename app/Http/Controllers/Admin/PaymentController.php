<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        Log::info("[PaymentController] Controller instantiated", [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'Unknown',
            'service' => PaymentService::class,
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Display payment requests with stats
     */
    public function index(Request $request)
    {
        $requestId = uniqid('payment_index_');
        Log::info("[{$requestId}] Payment index requested", [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Unknown',
            'request_params' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $status = $request->get('status', 'pending');
            
            Log::debug("[{$requestId}] Fetching payments data", [
                'status_filter' => $status,
                'page' => $request->get('page', 1)
            ]);

            // Use PaymentService for stats
            $statsStart = microtime(true);
            $stats = $this->paymentService->getPaymentStats();
            $statsTime = microtime(true) - $statsStart;
            
            Log::debug("[{$requestId}] Payment stats retrieved", array_merge($stats, [
                'execution_time_ms' => round($statsTime * 1000, 2)
            ]));

            // Fetch payments with filtering
            $paymentsStart = microtime(true);
            $payments = Payments::with(['invoice', 'order.customer.user'])
                ->when($status, function ($query, $status) {
                    if ($status !== 'all') {
                        return $query->where('status', $status);
                    }
                })
                ->latest()
                ->paginate(10);
            $paymentsTime = microtime(true) - $paymentsStart;

            // Get status counts
            $countsStart = microtime(true);
            $counts = [
                'all' => Payments::count(),
                'pending' => Payments::where('status', 'pending')->count(),
                'completed' => Payments::where('status', 'completed')->count(),
                'failed' => Payments::where('status', 'failed')->count(),
            ];
            $countsTime = microtime(true) - $countsStart;

            Log::info("[{$requestId}] Payment index data prepared successfully", [
                'payments_count' => $payments->count(),
                'total_pages' => $payments->lastPage(),
                'current_page' => $payments->currentPage(),
                'status_counts' => $counts,
                'performance' => [
                    'stats_time_ms' => round($statsTime * 1000, 2),
                    'payments_query_time_ms' => round($paymentsTime * 1000, 2),
                    'counts_time_ms' => round($countsTime * 1000, 2),
                    'total_time_ms' => round(($statsTime + $paymentsTime + $countsTime) * 1000, 2)
                ]
            ]);

            return view('source.admin.payments.index', compact('payments', 'status', 'counts', 'stats'));

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Payment index failed", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            return back()->with('error', 'Lỗi tải danh sách thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Approve payment
     */
    public function approve(Request $request, $id)
    {
        $requestId = uniqid('payment_approve_');
        Log::info("[{$requestId}] Payment approval requested", [
            'payment_id' => $id,
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()->name ?? 'Unknown',
            'admin_email' => Auth::user()->email ?? 'Unknown',
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        try {
            // Load payment with relationships
            $loadStart = microtime(true);
            $payment = Payments::with(['invoice', 'order.customer.user'])->findOrFail($id);
            $loadTime = microtime(true) - $loadStart;
            
            Log::info("[{$requestId}] Payment loaded for approval", [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'current_status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'customer_id' => $payment->order->customer_id ?? null,
                'customer_name' => $payment->order->customer->user->name ?? 'Unknown',
                'customer_email' => $payment->order->customer->user->email ?? 'Unknown',
                'load_time_ms' => round($loadTime * 1000, 2)
            ]);

            // Validate payment status
            if ($payment->status !== 'pending') {
                Log::warning("[{$requestId}] Attempted to approve non-pending payment", [
                    'payment_id' => $payment->id,
                    'current_status' => $payment->status,
                    'admin_id' => Auth::id()
                ]);
                return back()->with('error', 'Thanh toán đã được xử lý trước đó (Trạng thái: ' . $payment->status . ')');
            }

            // Process approval using service
            $approvalStart = microtime(true);
            $result = $this->paymentService->approvePayment($payment, Auth::id());
            $approvalTime = microtime(true) - $approvalStart;

            if ($result['success']) {
                Log::info("[{$requestId}] Payment approved successfully", [
                    'payment_id' => $payment->id,
                    'approved_by' => Auth::id(),
                    'provisions_created' => count($result['provisions'] ?? []),
                    'approval_time_ms' => round($approvalTime * 1000, 2),
                    'new_payment_status' => $result['payment']->status ?? 'unknown'
                ]);

                return redirect()->route('admin.payments.index')
                    ->with('success', 'Thanh toán đã được xác nhận và dịch vụ đã được cung cấp.');
            } else {
                Log::error("[{$requestId}] Payment approval failed (service returned false)", [
                    'payment_id' => $payment->id,
                    'result' => $result,
                    'approval_time_ms' => round($approvalTime * 1000, 2)
                ]);
                return back()->with('error', 'Không thể xác nhận thanh toán. Vui lòng thử lại.');
            }

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Payment approval exception", [
                'payment_id' => $id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Lỗi xử lý thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Reject payment with reason
     */
    public function reject(Request $request, $id)
    {
        $requestId = uniqid('payment_reject_');
        Log::info("[{$requestId}] Payment rejection requested", [
            'payment_id' => $id,
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()->name ?? 'Unknown',
            'request_data' => $request->except(['_token']),
            'ip' => $request->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // Validate input
        $validationStart = microtime(true);
        $request->validate(['reason' => 'required|string|max:255']);
        $validationTime = microtime(true) - $validationStart;

        Log::debug("[{$requestId}] Input validation passed", [
            'validation_time_ms' => round($validationTime * 1000, 2),
            'reason_length' => strlen($request->input('reason'))
        ]);

        try {
            // Load payment
            $loadStart = microtime(true);
            $payment = Payments::findOrFail($id);
            $loadTime = microtime(true) - $loadStart;
            
            $reason = $request->input('reason');

            Log::info("[{$requestId}] Payment loaded for rejection", [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'current_status' => $payment->status,
                'amount' => $payment->amount,
                'rejection_reason' => $reason,
                'load_time_ms' => round($loadTime * 1000, 2)
            ]);

            // Validate payment can be rejected
            if ($payment->status !== 'pending') {
                Log::warning("[{$requestId}] Attempted to reject non-pending payment", [
                    'payment_id' => $payment->id,
                    'current_status' => $payment->status,
                    'admin_id' => Auth::id()
                ]);
                return back()->with('error', 'Chỉ có thể từ chối thanh toán đang chờ xử lý (Trạng thái hiện tại: ' . $payment->status . ')');
            }

            // Process rejection using service
            $rejectionStart = microtime(true);
            $result = $this->paymentService->rejectPayment($payment, $reason, Auth::id());
            $rejectionTime = microtime(true) - $rejectionStart;

            Log::info("[{$requestId}] Payment rejected successfully", [
                'payment_id' => $payment->id,
                'rejected_by' => Auth::id(),
                'reason' => $reason,
                'rejection_time_ms' => round($rejectionTime * 1000, 2),
                'result_success' => $result['success'] ?? false
            ]);

            return redirect()->route('admin.payments.index')
                ->with('success', 'Thanh toán đã bị từ chối.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning("[{$requestId}] Payment rejection validation failed", [
                'payment_id' => $id,
                'validation_errors' => $e->errors(),
                'admin_id' => Auth::id()
            ]);
            
            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Payment rejection exception", [
                'payment_id' => $id,
                'reason' => $request->input('reason'),
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Show payment details
     */
    public function show(Request $request, $id)
    {
        $requestId = uniqid('payment_show_');
        Log::info("[{$requestId}] Payment details requested", [
            'payment_id' => $id,
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()->name ?? 'Unknown'
        ]);

        try {
            $loadStart = microtime(true);
            $payment = Payments::with([
                'invoice', 
                'order.customer.user', 
                'order.items.product'
            ])->findOrFail($id);
            $loadTime = microtime(true) - $loadStart;

            Log::info("[{$requestId}] Payment details loaded", [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'customer_name' => $payment->order->customer->user->name ?? 'Unknown',
                'load_time_ms' => round($loadTime * 1000, 2),
                'relationships_loaded' => [
                    'has_invoice' => !is_null($payment->invoice),
                    'has_order' => !is_null($payment->order),
                    'has_customer' => !is_null($payment->order->customer ?? null),
                    'order_items_count' => $payment->order->items->count() ?? 0
                ]
            ]);

            return view('source.admin.payments.show', compact('payment'));

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Payment details failed", [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect()->route('admin.payments.index')
                ->with('error', 'Không thể tải thông tin thanh toán: ' . $e->getMessage());
        }
    }
}