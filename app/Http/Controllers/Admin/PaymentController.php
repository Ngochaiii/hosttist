<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments;
use App\Models\Order_items;
use App\Models\ServiceProvision;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        // Keep existing index method
        $status = $request->get('status', 'pending');

        $stats = $this->paymentService->getPaymentStats();

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
            'payments'=> $payments,
        ];

        return view('source.admin.payments.index', compact('payments', 'status', 'counts', 'stats'));
    }

    /**
     * Show provision form khi approve payment
     */
    public function showProvisionForm($id)
    {
        $requestId = uniqid('provision_form_');

        Log::info("[{$requestId}] Showing provision form for payment", [
            'payment_id' => $id,
            'admin_id' => Auth::id()
        ]);

        try {
            $payment = Payments::with([
                'invoice.order.items.product',
                'order.customer.user'
            ])->findOrFail($id);

            // Kiểm tra payment status
            if ($payment->status !== 'pending') {
                return back()->with('error', 'Thanh toán đã được xử lý trước đó');
            }

            // Lấy order items
            $orderItems = [];
            if ($payment->invoice && $payment->invoice->order) {
                $orderItems = Order_items::where('order_id', $payment->invoice->order->id)
                    ->with('product')
                    ->get();
            }

            // Xác định product types cần provision
            $needsProvision = false;
            $productTypes = [];
            foreach ($orderItems as $item) {
                if ($item->product) {
                    $type = $item->product->type;
                    // Chỉ cần provision cho SSL, VPS, Hosting, Domain
                    if (in_array($type, ['ssl', 'vps', 'hosting', 'domain'])) {
                        $needsProvision = true;
                        $productTypes[$type] = true;
                    }
                }
            }

            // Nếu không cần provision, approve luôn
            if (!$needsProvision) {
                return $this->approveDirectly($payment);
            }

            Log::info("[{$requestId}] Provision form prepared", [
                'payment_id' => $payment->id,
                'order_items_count' => count($orderItems),
                'product_types' => array_keys($productTypes)
            ]);

            return view('source.admin.payments.provision-form', compact('payment', 'orderItems', 'productTypes'));
        } catch (\Exception $e) {
            Log::error("[{$requestId}] Error showing provision form", [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Lỗi hiển thị form: ' . $e->getMessage());
        }
    }

    /**
     * Approve payment với provision data
     */
    public function approveWithProvision(Request $request, $id)
    {
        $requestId = uniqid('payment_approve_provision_');

        Log::info("[{$requestId}] Payment approval with provision requested", [
            'payment_id' => $id,
            'admin_id' => Auth::id()
        ]);

        try {
            DB::beginTransaction();

            $payment = Payments::with([
                'invoice.order.items.product',
                'order.customer.user'
            ])->findOrFail($id);

            // Validate payment status
            if ($payment->status !== 'pending') {
                DB::rollback();
                return back()->with('error', 'Thanh toán đã được xử lý trước đó');
            }

            // Update payment status
            $payment->status = 'completed';
            $payment->verified_by = Auth::id();
            $payment->verified_at = now();
            $payment->save();

            // Update invoice
            if ($payment->invoice) {
                $payment->invoice->update(['status' => 'paid']);
            }

            // Update order
            if ($payment->order) {
                $payment->order->update(['status' => 'processing']);
            }

            // Create provisions với data từ form
            $provisions = [];
            $orderItems = Order_items::where('order_id', $payment->invoice->order->id)
                ->with('product')
                ->get();

            foreach ($orderItems as $item) {
                if (!$item->product) continue;

                $productType = $item->product->type;

                // Chỉ tạo provision cho những loại cần
                if (!in_array($productType, ['ssl', 'vps', 'hosting', 'domain'])) {
                    continue;
                }

                $provisionData = $request->input('provision_data.' . $item->id, []);

                // Xử lý data theo từng loại
                $processedData = $this->processProvisionData($productType, $provisionData, $request, $item);

                // Tạo provision record
                $provision = ServiceProvision::create([
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'customer_id' => $payment->order->customer_id,
                    'provision_type' => $productType,
                    'provision_status' => 'completed', // Đánh dấu completed luôn vì đã có data
                    'provision_data' => $processedData,
                    'provisioned_by' => Auth::id(),
                    'provisioned_at' => now(),
                    'priority' => 5,
                    'provision_notes' => $provisionData['notes'] ?? 'Provisioned via payment approval'
                ]);

                $provisions[] = $provision;

                Log::info("[{$requestId}] Provision created", [
                    'provision_id' => $provision->id,
                    'product_type' => $productType,
                    'order_item_id' => $item->id
                ]);
            }

            DB::commit();

            Log::info("[{$requestId}] Payment approved with provisions", [
                'payment_id' => $payment->id,
                'provisions_count' => count($provisions)
            ]);

            // Send notification emails
            if ($payment->order && $payment->order->customer && $payment->order->customer->user) {
                $this->sendProvisionEmail($payment, $provisions);
            }

            return redirect()->route('admin.payments.index')
                ->with('success', 'Thanh toán đã được xác nhận và thông tin dịch vụ đã được cập nhật.');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error("[{$requestId}] Payment approval failed", [
                'payment_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Lỗi xử lý: ' . $e->getMessage());
        }
    }

    /**
     * Process provision data theo product type
     */
    private function processProvisionData($productType, $provisionData, Request $request, $item)
    {
        $data = [];

        switch ($productType) {
            case 'vps':
                $data = [
                    'server_ip' => $provisionData['server_ip'] ?? null,
                    'username' => $provisionData['username'] ?? null,
                    'password' => isset($provisionData['password']) ? encrypt($provisionData['password']) : null,
                    'port' => $provisionData['port'] ?? 22,
                    'os' => $provisionData['os'] ?? null,
                    'root_password' => isset($provisionData['password']) ? encrypt($provisionData['password']) : null,
                    'created_at' => now()->toISOString()
                ];
                break;

            case 'hosting':
                $data = [
                    'cpanel_username' => $provisionData['cpanel_username'] ?? null,
                    'cpanel_password' => isset($provisionData['cpanel_password']) ? encrypt($provisionData['cpanel_password']) : null,
                    'server_name' => $provisionData['server_name'] ?? null,
                    'nameservers' => $provisionData['nameservers'] ?? null,
                    'ftp_username' => $provisionData['ftp_username'] ?? $provisionData['cpanel_username'] ?? null,
                    'ftp_password' => isset($provisionData['ftp_password']) ? encrypt($provisionData['ftp_password']) : null,
                    'created_at' => now()->toISOString()
                ];
                break;

            case 'ssl':
                $data = ['domain' => $item->domain];

                // Handle file uploads
                if ($request->hasFile("provision_data.{$item->id}.certificate_file")) {
                    $certFile = $request->file("provision_data.{$item->id}.certificate_file");
                    $certPath = $certFile->store('ssl_certificates/' . $item->id, 'private');
                    $data['certificate_path'] = $certPath;
                    $data['certificate'] = file_get_contents($certFile->getRealPath());
                }

                if ($request->hasFile("provision_data.{$item->id}.private_key_file")) {
                    $keyFile = $request->file("provision_data.{$item->id}.private_key_file");
                    $keyPath = $keyFile->store('ssl_certificates/' . $item->id, 'private');
                    $data['private_key_path'] = $keyPath;
                    $data['private_key'] = encrypt(file_get_contents($keyFile->getRealPath()));
                }

                if ($request->hasFile("provision_data.{$item->id}.ca_bundle_file")) {
                    $caFile = $request->file("provision_data.{$item->id}.ca_bundle_file");
                    $caPath = $caFile->store('ssl_certificates/' . $item->id, 'private');
                    $data['ca_bundle_path'] = $caPath;
                    $data['ca_bundle'] = file_get_contents($caFile->getRealPath());
                }

                $data['expiry_date'] = $provisionData['expiry_date'] ?? null;
                $data['ssl_provider'] = $provisionData['ssl_provider'] ?? null;
                $data['created_at'] = now()->toISOString();
                break;

            case 'domain':
                $data = [
                    'domain_name' => $item->domain,
                    'registrar' => $provisionData['registrar'] ?? null,
                    'nameservers' => $provisionData['nameservers'] ?? null,
                    'expiry_date' => $provisionData['expiry_date'] ?? null,
                    'auth_code' => $provisionData['auth_code'] ?? \Illuminate\Support\Str::random(16),
                    'created_at' => now()->toISOString()
                ];
                break;

            default:
                $data = [
                    'notes' => $provisionData['notes'] ?? 'Service activated',
                    'created_at' => now()->toISOString()
                ];
        }

        return $data;
    }

    /**
     * Approve directly không cần provision
     */
    private function approveDirectly($payment)
    {
        try {
            $result = $this->paymentService->approvePayment($payment, Auth::id());

            if ($result['success']) {
                return redirect()->route('admin.payments.index')
                    ->with('success', 'Thanh toán đã được xác nhận.');
            }

            return back()->with('error', 'Không thể xác nhận thanh toán.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Send provision email to customer
     */
    private function sendProvisionEmail($payment, $provisions)
    {
        // Implement email logic here
        // You can use your existing EmailService
    }

    /**
     * Original approve method - redirect to provision form
     */
    public function approve(Request $request, $id)
    {
        // Redirect to provision form instead of direct approval
        return redirect()->route('admin.payments.provision-form', $id);
    }

    // Keep existing reject method
    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        try {
            $payment = Payments::findOrFail($id);

            if ($payment->status !== 'pending') {
                return back()->with('error', 'Chỉ có thể từ chối thanh toán đang chờ xử lý');
            }

            $result = $this->paymentService->rejectPayment($payment, $request->input('reason'), Auth::id());

            return redirect()->route('admin.payments.index')
                ->with('success', 'Thanh toán đã bị từ chối.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
