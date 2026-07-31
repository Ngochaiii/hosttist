<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payments;
use App\Models\Order_items;
use App\Models\ServiceProvision;
use App\Services\EmailService;
use App\Services\OrderService;
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

            // Lock payment row để chống race-condition khi 2 admin cùng duyệt.
            $payment = Payments::where('id', $id)->lockForUpdate()->first();
            if (!$payment) {
                DB::rollback();
                return back()->with('error', 'Không tìm thấy thanh toán');
            }
            $payment->load(['invoice.order.items.product', 'order.customer.user']);

            // Validate payment status SAU khi đã lock — đảm bảo state mới nhất.
            if ($payment->status !== 'pending') {
                DB::rollback();
                return back()->with('error', 'Thanh toán đã được xử lý trước đó');
            }

            // Đơn gia hạn: bỏ qua toàn bộ provision, delegate cho PaymentService
            // (tránh tạo trùng ServiceProvision + CustomerService cho dịch vụ đã có).
            $renewalOrder = $payment->order ?? ($payment->invoice->order ?? null);
            if ($renewalOrder && $renewalOrder->renewal_of_service_id) {
                DB::rollback();
                Log::info("[{$requestId}] Renewal payment detected - approve without provision", [
                    'payment_id' => $id,
                    'order_id'   => $renewalOrder->id,
                    'service_id' => $renewalOrder->renewal_of_service_id,
                ]);
                return $this->approveDirectly($payment);
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
                    'is_provisionable' => in_array($serviceType, OrderService::PROVISIONABLE_TYPES)
                ]);

                if (!in_array($serviceType, OrderService::PROVISIONABLE_TYPES)) {
                    Log::warning("Skipping item - service type not provisionable", [
                        'item_id' => $item->id,
                        'service_type' => $serviceType
                    ]);
                    continue;
                }

                // Validate required fields theo từng service type — admin phải nhập đủ thông tin
                // bắt buộc trước khi tạo provision (tránh tạo CS với credentials trống → khách
                // không thể truy cập dịch vụ).
                $missing = $this->validateProvisionPayload($serviceType, $itemProvisionData, $itemFiles, $item);
                if (!empty($missing)) {
                    DB::rollback();
                    Log::warning("[{$requestId}] Provision data invalid", [
                        'item_id'      => $item->id,
                        'service_type' => $serviceType,
                        'missing'      => $missing,
                    ]);
                    return back()->withInput()->with(
                        'error',
                        "Thiếu thông tin provision cho item #{$item->id} ({$serviceType}): " . implode(', ', $missing)
                    );
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

            // Kích hoạt CustomerService lifecycle + báo cho khách "dịch vụ đã sẵn sàng"
            // (provision ở nhánh này tạo với status=completed ngay). Trước đây khách chỉ
            // nhận "Thanh toán đã xác nhận", không có thông báo dịch vụ sẵn sàng trên UI.
            foreach ($provisions as $provision) {
                try {
                    $this->lifecycle->activateFromProvision($provision);
                } catch (\Exception $e) {
                    Log::error("[{$requestId}] activateFromProvision failed", [
                        'provision_id' => $provision->id,
                        'error'        => $e->getMessage(),
                    ]);
                }

                // Thông báo in-app "dịch vụ sẵn sàng" — không throw, không chặn flow duyệt.
                try {
                    $customerUser = $provision->customer?->user ?? $payment->order?->customer?->user;
                    if ($customerUser) {
                        $customerUser->notify(new \App\Notifications\CustomerServiceReady($provision));
                    }
                } catch (\Throwable $e) {
                    Log::error("[{$requestId}] CustomerServiceReady notify failed", [
                        'provision_id' => $provision->id,
                        'error'        => $e->getMessage(),
                    ]);
                }
            }

            // Nhánh này tạo provision với status=completed ngay nên order phải được
            // chốt luôn. markProvisionCompleted() có gọi bước này, còn nhánh duyệt kèm
            // provision thì không → order kẹt ở 'processing' dù dịch vụ đã bàn giao.
            if ($payment->order) {
                app(OrderService::class)->updateOrderOnProvisionComplete(
                    $payment->order->fresh()->load('items')
                );
            }

            // Luôn gửi thông báo in-app xác nhận thanh toán cho khách sau khi approve
            $this->emailService->notifyPaymentApproved(
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

            return back()->with('error', 'Không thể xử lý thanh toán. Vui lòng kiểm tra log hoặc thử lại.');
        }
    }

    /**
     * Required fields theo service type. Trả về list field name bị thiếu.
     * Domain (cho ssl/hosting/domain) lấy từ options của order item nếu không có trong payload.
     */
    private function validateProvisionPayload(string $serviceType, array $payload, array $files, $item): array
    {
        $itemOptions = json_decode($item->options, true) ?: [];
        $domain = $payload['domain'] ?? ($itemOptions['domain'] ?? $item->domain ?? null);

        $missing = [];
        switch ($serviceType) {
            case 'vps':
                foreach (['server_ip', 'username', 'password'] as $f) {
                    if (empty($payload[$f])) $missing[] = $f;
                }
                break;
            case 'hosting':
            case 'cloud_hosting':
                if (empty($domain)) $missing[] = 'domain';
                foreach (['cpanel_username', 'cpanel_password', 'cpanel_url'] as $f) {
                    if (empty($payload[$f])) $missing[] = $f;
                }
                break;
            case 'ssl':
                if (empty($domain)) $missing[] = 'domain';
                // Cần ít nhất certificate + private_key (file hoặc đã upload trước đó)
                if (empty($files['certificate'])) $missing[] = 'certificate file';
                if (empty($files['private_key'])) $missing[] = 'private_key file';
                break;
            case 'domain':
                if (empty($domain)) $missing[] = 'domain';
                if (empty($payload['registrar'])) $missing[] = 'registrar';
                break;
            // Các loại khác (email, anti_ddos, reseller, advertising, web_design, seo)
            // không có schema cứng — chỉ cần notes là đủ; không validate.
        }

        return $missing;
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

                // Handle SSL files - dùng file_put_contents trực tiếp, không cần fileinfo
                $sslFiles = [];
                if (!empty($files)) {
                    $sslDir = storage_path("app/ssl_certificates/{$item->id}");
                    if (!is_dir($sslDir)) {
                        mkdir($sslDir, 0750, true);
                    }

                    $fileMap = [
                        'certificate' => 'certificate.pem',
                        'private_key' => 'private_key.pem',
                        'ca_bundle'   => 'ca_bundle.pem',
                    ];

                    foreach ($fileMap as $field => $filename) {
                        if (isset($files[$field])) {
                            $uploadedFile = $files[$field];
                            $destPath = $sslDir . '/' . $filename;
                            $content = file_get_contents($uploadedFile->getRealPath());
                            file_put_contents($destPath, $content);
                            $sslFiles[$field] = $content;
                            $data["{$field}_path"] = "ssl_certificates/{$item->id}/{$filename}";
                        }
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
                // Thông báo in-app xác nhận
                $this->emailService->notifyPaymentApproved(
                    $payment->fresh()->load(['order.customer.user', 'order.items', 'invoice'])
                );

                return redirect()->route('admin.payments.index')
                    ->with('success', 'Thanh toán đã được xác nhận.');
            }

            return back()->with('error', 'Không thể xác nhận thanh toán.');
        } catch (\Exception $e) {
            Log::error('approveDirectly failed: ' . $e->getMessage(), ['payment_id' => $payment->id ?? null]);
            return back()->with('error', 'Không thể xác nhận thanh toán. Vui lòng kiểm tra log hoặc thử lại.');
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        try {
            $payment = DB::transaction(function () use ($id, $request) {
                // Lock + double-check status để chống race với approve.
                $p = Payments::where('id', $id)->lockForUpdate()->first();
                if (!$p) {
                    throw new \Exception('Không tìm thấy thanh toán');
                }
                if ($p->status !== 'pending') {
                    throw new \Exception('Chỉ có thể từ chối thanh toán đang chờ xử lý');
                }
                $this->paymentService->rejectPayment($p, $request->input('reason'), Auth::id());
                return $p;
            });

            return redirect()->route('admin.payments.index')
                ->with('success', 'Thanh toán đã bị từ chối.');
        } catch (\Exception $e) {
            Log::error('Reject payment failed: ' . $e->getMessage(), ['payment_id' => $id]);
            return back()->with('error', 'Không thể từ chối thanh toán. Vui lòng kiểm tra log hoặc thử lại.');
        }
    }
}
