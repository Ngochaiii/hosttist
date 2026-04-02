<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments;
use App\Models\Order_items;
use App\Models\ServiceProvision;
use App\Services\EmailService;
use App\Services\ServiceLifecycleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $emailService;
    protected $lifecycle;

    public function __construct(
        PaymentService        $paymentService,
        EmailService          $emailService,
        ServiceLifecycleService $lifecycle
    ) {
        $this->paymentService = $paymentService;
        $this->emailService   = $emailService;
        $this->lifecycle      = $lifecycle;
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
            'payments' => $payments,
        ];
        $domainItems = [];
        return view('source.admin.payments.index', compact('payments', 'status', 'counts', 'stats', 'domainItems'));
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
     * Approve payment với provision data - FIXED
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
            $payment->update([
                'status'      => 'completed',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

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

            // FIX: Lấy provision data đúng cấu trúc
            $provisionData = $request->input('provision', []);
            $provisionFiles = $request->file('provision_files', []);
            Log::info("Request debug", [
                'all_inputs' => $request->all(),
                'provision_input' => $request->input('provision'),
                'files' => $request->hasFile('provision_files') ? 'yes' : 'no'
            ]);
            foreach ($orderItems as $item) {
                Log::info("Processing order item", [
                    'item_id' => $item->id,
                    'product_type' => $item->product->type ?? 'null',
                    'has_product' => !is_null($item->product)
                ]);

                if (!$item->product) continue;

                $itemProvisionData = $provisionData[$item->id] ?? [];
                if (empty($itemProvisionData)) {
                    continue;
                }

                $itemProvisionData = $provisionData[$item->id] ?? [];
                $itemFiles = $provisionFiles[$item->id] ?? [];

                Log::info("Provision data check", [
                    'item_id' => $item->id,
                    'has_provision_data' => !empty($itemProvisionData),
                    'provision_data_keys' => array_keys($itemProvisionData),
                    'provision_data' => $itemProvisionData
                ]);

                if (empty($itemProvisionData)) {
                    Log::warning("Skipping item - no provision data", ['item_id' => $item->id]);
                    continue;
                }

                $serviceType = $itemProvisionData['service_type'] ?? null;

                Log::info("Service type from provision data", [
                    'item_id' => $item->id,
                    'service_type' => $serviceType,
                    'is_provisionable' => in_array($serviceType, ['ssl', 'vps', 'hosting', 'domain'])
                ]);

                if (!in_array($serviceType, ['ssl', 'vps', 'hosting', 'domain'])) {
                    Log::warning("Skipping item - service type not provisionable", [
                        'item_id' => $item->id,
                        'service_type' => $serviceType
                    ]);
                    continue;
                }

                // Xử lý data theo service type
                $processedData = $this->processProvisionData($serviceType, $itemProvisionData, $itemFiles, $item);

                // Tạo provision record
                $provision = ServiceProvision::create([
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'customer_id' => $payment->order->customer_id,
                    'provision_type' => $serviceType,
                    'provision_status' => 'completed',
                    'provision_data' => json_encode($processedData),
                    'provisioned_by' => Auth::id(),
                    'provisioned_at' => now(),
                    'priority' => 5,
                    'provision_notes' => $itemProvisionData['notes'] ?? 'Provisioned via payment approval',
                    'customer_viewed' => false
                ]);

                $provisions[] = $provision;

                Log::info("[{$requestId}] Provision created", [
                    'provision_id' => $provision->id,
                    'service_type' => $serviceType,
                    'order_item_id' => $item->id
                ]);
            }

            DB::commit();

            Log::info("[{$requestId}] Payment approved with provisions", [
                'payment_id'       => $payment->id,
                'provisions_count' => count($provisions),
            ]);

            // Kích hoạt CustomerService lifecycle cho từng provision vừa tạo
            foreach ($provisions as $provision) {
                try {
                    $this->lifecycle->activateFromProvision($provision);
                } catch (\Exception $e) {
                    Log::error("[{$requestId}] activateFromProvision failed", [
                        'provision_id' => $provision->id,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }

            // Luôn gửi email xác nhận thanh toán cho khách sau khi approve
            $this->emailService->sendPaymentApprovedEmail(
                $payment->fresh()->load(['order.customer.user', 'order.items', 'invoice'])
            );

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
     * Process provision data theo product type - FIXED
     */
    private function processProvisionData($productType, $provisionData, $files, $item)
    {
        $data = [];
        $itemOptions = json_decode($item->options, true) ?: [];

        switch ($productType) {
            case 'vps':
                $data = [
                    'service_type' => 'vps',
                    'server_ip' => $provisionData['server_ip'] ?? null,
                    'username' => $provisionData['username'] ?? null,
                    'password' => isset($provisionData['password']) ? encrypt($provisionData['password']) : null,
                    'port' => $provisionData['port'] ?? 22,
                    'os' => $provisionData['os'] ?? null,
                    'control_panel_url' => $provisionData['control_panel_url'] ?? null,
                    'credentials' => [
                        'server_ip' => $provisionData['server_ip'] ?? null,
                        'username' => $provisionData['username'] ?? null,
                        'password' => $provisionData['password'] ?? null, // Plain for credentials display
                        'port' => $provisionData['port'] ?? 22,
                        'os' => $provisionData['os'] ?? null,
                        'control_panel_url' => $provisionData['control_panel_url'] ?? null,
                    ],
                    'status' => 'active',
                    'created_at' => now()->toISOString(),
                    'notes' => $provisionData['notes'] ?? ''
                ];
                break;

            case 'hosting':
                $data = [
                    'service_type' => 'hosting',
                    'domain' => $itemOptions['domain'] ?? $item->domain,
                    'cpanel_username' => $provisionData['cpanel_username'] ?? null,
                    'cpanel_password' => isset($provisionData['cpanel_password']) ? encrypt($provisionData['cpanel_password']) : null,
                    'cpanel_url' => $provisionData['cpanel_url'] ?? null,
                    'server_name' => $provisionData['server_name'] ?? null,
                    'nameservers' => $provisionData['nameservers'] ?? null,
                    'ftp_host' => $provisionData['ftp_host'] ?? null,
                    'ftp_username' => $provisionData['ftp_username'] ?? null,
                    'ftp_password' => isset($provisionData['ftp_password']) ? encrypt($provisionData['ftp_password']) : null,
                    'credentials' => [
                        'cpanel_url' => $provisionData['cpanel_url'] ?? null,
                        'cpanel_username' => $provisionData['cpanel_username'] ?? null,
                        'cpanel_password' => $provisionData['cpanel_password'] ?? null, // Plain for display
                        'ftp_host' => $provisionData['ftp_host'] ?? null,
                        'ftp_username' => $provisionData['ftp_username'] ?? null,
                        'ftp_password' => $provisionData['ftp_password'] ?? null, // Plain for display
                        'nameservers' => $provisionData['nameservers'] ?? null,
                        'server_name' => $provisionData['server_name'] ?? null,
                    ],
                    'status' => 'active',
                    'created_at' => now()->toISOString(),
                    'notes' => $provisionData['notes'] ?? ''
                ];
                break;

            case 'ssl':
                $data = [
                    'service_type' => 'ssl',
                    'domain' => $itemOptions['domain'] ?? $item->domain,
                    'ssl_provider' => $provisionData['ssl_provider'] ?? null,
                    'expiry_date' => $provisionData['expiry_date'] ?? null,
                    'status' => 'active',
                    'created_at' => now()->toISOString(),
                    'notes' => $provisionData['notes'] ?? ''
                ];

                // FIX: Handle SSL files đúng cách
                $sslFiles = [];
                if (!empty($files)) {
                    $sslDir = "ssl_certificates/{$item->id}";

                    if (isset($files['certificate'])) {
                        $certFile = $files['certificate'];
                        $certPath = $certFile->store($sslDir, 'private');
                        $certContent = file_get_contents($certFile->getRealPath());
                        $sslFiles['certificate'] = $certContent;
                        $data['certificate_path'] = $certPath;
                    }

                    if (isset($files['private_key'])) {
                        $keyFile = $files['private_key'];
                        $keyPath = $keyFile->store($sslDir, 'private');
                        $keyContent = file_get_contents($keyFile->getRealPath());
                        $sslFiles['private_key'] = $keyContent;
                        $data['private_key_path'] = $keyPath;
                    }

                    if (isset($files['ca_bundle'])) {
                        $caFile = $files['ca_bundle'];
                        $caPath = $caFile->store($sslDir, 'private');
                        $caContent = file_get_contents($caFile->getRealPath());
                        $sslFiles['ca_bundle'] = $caContent;
                        $data['ca_bundle_path'] = $caPath;
                    }

                    if (!empty($sslFiles)) {
                        $data['ssl_files'] = $sslFiles;
                    }
                }
                break;

            case 'domain':
                $data = [
                    'service_type' => 'domain',
                    'domain_name' => $itemOptions['domain'] ?? $item->domain,
                    'registrar' => $provisionData['registrar'] ?? null,
                    'nameservers' => $provisionData['nameservers'] ?? null,
                    'expiry_date' => $provisionData['expiry_date'] ?? null,
                    'auth_code' => $provisionData['auth_code'] ?? \Illuminate\Support\Str::random(16),
                    'control_panel_url' => $provisionData['control_panel_url'] ?? null,
                    'credentials' => [
                        'registrar' => $provisionData['registrar'] ?? null,
                        'nameservers' => $provisionData['nameservers'] ?? null,
                        'auth_code' => $provisionData['auth_code'] ?? \Illuminate\Support\Str::random(16),
                        'control_panel_url' => $provisionData['control_panel_url'] ?? null,
                    ],
                    'status' => 'active',
                    'created_at' => now()->toISOString(),
                    'notes' => $provisionData['notes'] ?? ''
                ];
                break;

            default:
                $data = [
                    'service_type' => $productType,
                    'status' => 'active',
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
                // Gửi email xác nhận
                $this->emailService->sendPaymentApprovedEmail(
                    $payment->fresh()->load(['order.customer.user', 'order.items', 'invoice'])
                );

                return redirect()->route('admin.payments.index')
                    ->with('success', 'Thanh toán đã được xác nhận.');
            }

            return back()->with('error', 'Không thể xác nhận thanh toán.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Original approve method - redirect to provision form
     */
    public function approve($id)
    {
        return redirect()->route('admin.payments.provision-form', $id);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        try {
            $payment = Payments::findOrFail($id);

            if ($payment->status !== 'pending') {
                return back()->with('error', 'Chỉ có thể từ chối thanh toán đang chờ xử lý');
            }

            $this->paymentService->rejectPayment($payment, $request->input('reason'), Auth::id());

            return redirect()->route('admin.payments.index')
                ->with('success', 'Thanh toán đã bị từ chối.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
