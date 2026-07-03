<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deposit;
use App\Models\Customers;
use Illuminate\Support\Facades\Auth;
use App\Mail\DepositApproved;
use App\Mail\DepositRejected;
use App\Models\deposits;
use App\Notifications\CustomerAlert;
use App\Services\EmailService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DepositController extends Controller
{
    /**
     * Hiển thị danh sách yêu cầu nạp tiền
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $deposits = deposits::with(['customer.user'])
            ->when($status, function ($query, $status) {
                if ($status !== 'all') {
                    return $query->where('status', $status);
                }
            })
            ->latest()
            ->paginate(10);

        $counts = [
            'all' => deposits::count(),
            'pending' => deposits::where('status', 'pending')->count(),
            'approved' => deposits::where('status', 'approved')->count(),
            'rejected' => deposits::where('status', 'rejected')->count(),
        ];

        // Thêm dữ liệu thống kê
        $stats = [
            'today_deposits' => deposits::whereDate('created_at', Carbon::today())
                ->where('status', 'approved')
                ->sum('amount'),
            'total_approved' => deposits::where('status', 'approved')->sum('amount'),
            'total_pending' => deposits::where('status', 'pending')->sum('amount'),
            'total_rejected' => deposits::where('status', 'rejected')->sum('amount'),
        ];

        // Dữ liệu biểu đồ cho 7 ngày gần đây
        $chart_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chart_data[] = [
                'date' => $date->format('d/m'),
                'approved' => deposits::whereDate('created_at', $date)
                    ->where('status', 'approved')
                    ->sum('amount'),
                'pending' => deposits::whereDate('created_at', $date)
                    ->where('status', 'pending')
                    ->sum('amount'),
                'rejected' => deposits::whereDate('created_at', $date)
                    ->where('status', 'rejected')
                    ->sum('amount'),
            ];
        }

        return view('source.admin.deposits.index', compact('deposits', 'status', 'counts'));
    }

    /**
     * Hiển thị chi tiết yêu cầu nạp tiền
     */
    public function show($id)
    {
        $deposit = deposits::with(['customer.user', 'verifier'])->findOrFail($id);
        return view('source.admin.deposits.show', compact('deposit'));
    }

    /**
     * Xác nhận yêu cầu nạp tiền
     */
    public function approve(Request $request, $id)
    {
        $deposit = deposits::with('customer.user')->findOrFail($id);

        // Kiểm tra trạng thái
        if ($deposit->status !== 'pending') {
            return back()->with('error', 'Yêu cầu này đã được xử lý trước đó.');
        }

        // Cập nhật trạng thái deposit
        $deposit->status = 'approved';
        $deposit->verified_by = Auth::id();
        $deposit->verified_at = now();
        $deposit->save();

        // Cộng tiền vào tài khoản khách hàng
        $customer = $deposit->customer;
        $customer->balance += $deposit->amount;
        $customer->save();

        // Thông báo in-app cho khách (không phụ thuộc email)
        if ($deposit->customer && $deposit->customer->user) {
            app(EmailService::class)->notifyInApp($deposit->customer->user, new CustomerAlert(
                'deposit_approved',
                'Nạp tiền thành công',
                'Yêu cầu nạp tiền #' . $deposit->transaction_code . ' ('
                    . number_format($deposit->amount) . ' đ) đã được duyệt. Số dư mới: '
                    . number_format($customer->balance) . ' đ.',
                route('customer.profile'),
                'success'
            ), 'deposit_approve_' . $deposit->id);
        }

        // Gửi email thông báo cho khách hàng
        if ($deposit->customer && $deposit->customer->user && $deposit->customer->user->email) {
            try {
                Mail::to($deposit->customer->user->email)
                    ->send(new DepositApproved([
                        'transaction_code' => $deposit->transaction_code,
                        'amount' => $deposit->amount,
                        'customer_name' => $deposit->customer->user->name,
                        'date' => $deposit->verified_at->format('d/m/Y H:i:s'),
                        'new_balance' => $customer->balance,
                    ]));
            } catch (\Exception $e) {
                Log::error('Lỗi gửi email xác nhận: ' . $e->getMessage());
            }
        }

        return redirect()->route('deposits.index')
            ->with('success', 'Yêu cầu nạp tiền đã được xác nhận và số dư khách hàng đã được cập nhật.');
    }

    /**
     * Từ chối yêu cầu nạp tiền
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $deposit = deposits::with('customer.user')->findOrFail($id);

        // Kiểm tra trạng thái
        if ($deposit->status !== 'pending') {
            return back()->with('error', 'Yêu cầu này đã được xử lý trước đó.');
        }

        // Cập nhật trạng thái deposit
        $deposit->status = 'rejected';
        $deposit->verified_by = Auth::id();
        $deposit->verified_at = now();
        $deposit->save();

        // Thông báo in-app cho khách (không phụ thuộc email)
        if ($deposit->customer && $deposit->customer->user) {
            app(EmailService::class)->notifyInApp($deposit->customer->user, new CustomerAlert(
                'deposit_rejected',
                'Yêu cầu nạp tiền bị từ chối',
                'Yêu cầu nạp tiền #' . $deposit->transaction_code . ' ('
                    . number_format($deposit->amount) . ' đ) đã bị từ chối. Lý do: ' . $request->reason,
                route('customer.profile'),
                'danger'
            ), 'deposit_reject_' . $deposit->id);
        }

        // Gửi email thông báo cho khách hàng
        if ($deposit->customer && $deposit->customer->user && $deposit->customer->user->email) {
            try {
                Mail::to($deposit->customer->user->email)
                    ->send(new DepositRejected([
                        'transaction_code' => $deposit->transaction_code,
                        'amount' => $deposit->amount,
                        'customer_name' => $deposit->customer->user->name,
                        'reason' => $request->reason,
                        'date' => $deposit->verified_at->format('d/m/Y H:i:s'),
                    ]));
            } catch (\Exception $e) {
                Log::error('Lỗi gửi email từ chối: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.deposits.index')
            ->with('success', 'Yêu cầu nạp tiền đã bị từ chối.');
    }
}
