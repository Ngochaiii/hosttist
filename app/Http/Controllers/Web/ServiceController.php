<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{ServiceProvision, ProvisionLog, Products, CustomerService, Config, Invoices};
use App\Services\{ProvisionService, ServiceLifecycleService, PaymentService, QuotePdfService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    protected $provisionService;
    protected $lifecycle;

    public function __construct(ProvisionService $provisionService, ServiceLifecycleService $lifecycle)
    {
        $this->middleware('frontend.auth');
        $this->provisionService = $provisionService;
        $this->lifecycle        = $lifecycle;
    }

    /**
     * Hiển thị danh sách services của customer
     */
    public function index(Request $request)
    {
        $customer = auth()->user()->customer;
        if (!$customer) {
            return redirect()->route('customer.profile')
                ->with('error', 'Vui lòng cập nhật thông tin khách hàng để xem dịch vụ.');
        }

        // Get ServiceProvisions (records trong bảng service_provisions)
        $provisionQuery = ServiceProvision::where('customer_id', $customer->id)
            ->with(['product', 'orderItem.order', 'customerService']);

        // Get Customer Services (records trong bảng products với customer_id)
        $customerServicesQuery = Products::where('customer_id', $customer->id)
            ->whereNotNull('customer_id');

        // Apply filters
        if ($request->filled('status')) {
            if (in_array($request->status, ['pending', 'processing', 'completed', 'failed', 'cancelled'])) {
                // Filter provisions
                $provisionQuery->where('provision_status', $request->status);
            }

            if (in_array($request->status, ['active', 'expired', 'suspended', 'cancelled'])) {
                // Filter customer services
                $customerServicesQuery->where('service_status', $request->status);
            }
        }

        if ($request->filled('type')) {
            $provisionQuery->whereHas('product', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
            $customerServicesQuery->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $provisionQuery->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
            $customerServicesQuery->where('name', 'like', "%{$search}%");
        }

        // Get data
        $provisions = $provisionQuery->latest()->paginate(10, ['*'], 'provisions_page');
        $customerServices = $customerServicesQuery->latest()->paginate(10, ['*'], 'services_page');

        // Statistics for provisions
        $provisionStats = [
            'total' => ServiceProvision::where('customer_id', $customer->id)->count(),
            'pending' => ServiceProvision::where('customer_id', $customer->id)
                ->where('provision_status', 'pending')->count(),
            'processing' => ServiceProvision::where('customer_id', $customer->id)
                ->where('provision_status', 'processing')->count(),
            'completed' => ServiceProvision::where('customer_id', $customer->id)
                ->where('provision_status', 'completed')->count(),
            'failed' => ServiceProvision::where('customer_id', $customer->id)
                ->where('provision_status', 'failed')->count(),
        ];

        // Statistics for customer services
        $serviceStats = [
            'total' => Products::where('customer_id', $customer->id)->count(),
            'active' => Products::where('customer_id', $customer->id)
                ->where('service_status', 'active')->count(),
            'expired' => Products::where('customer_id', $customer->id)
                ->where('service_status', 'expired')->count(),
            'suspended' => Products::where('customer_id', $customer->id)
                ->where('service_status', 'suspended')->count(),
        ];

        return view('source.web.services.index', compact(
            'provisions',
            'customerServices',
            'provisionStats',
            'serviceStats'
        ));
    }

    /**
     * Hiển thị chi tiết service provision
     */
    public function showProvision($id)
    {
        $provision = $this->findProvision($id);
        $provision->markAsViewed();
        $logs = $provision->logs()->latest()->limit(10)->get();

        $provisionData = json_decode($provision->provision_data, true) ?? [];

        // View services.show viết cho Products legacy (đọc name/type/start_date/end_date/
        // next_due_date/price...). ServiceProvision KHÔNG có các field đó → ngày hiển thị
        // loạn/N/A. Chuẩn hoá về đúng NGUỒN: hạn dùng lấy từ CustomerService (nguồn chân lý),
        // ngày cung cấp lấy từ provisioned_at, giá/định kỳ lấy từ product.
        $cs      = $provision->customerService;
        $product = $provision->product;

        $provision->setAttribute('name', $product?->name ?? ('Dịch vụ #' . $provision->id));
        $provision->setAttribute('sku', $product?->sku);
        $provision->setAttribute('type', $provision->provision_type);
        $provision->setAttribute('service_status', $cs?->status
            ?? ($provision->provision_status === 'completed' ? 'active' : $provision->provision_status));
        // Ngày bắt đầu = ngày CUNG CẤP thực tế (provisioned_at) để khớp với trang
        // credentials ("Ngày kích hoạt"); fallback started_at của CustomerService.
        $provision->setAttribute('start_date', $provision->provisioned_at ?? $cs?->started_at);
        $provision->setAttribute('end_date', $cs?->expires_at);
        $provision->setAttribute('next_due_date', $cs?->next_renewal_date);
        $provision->setAttribute('auto_renew', (bool) ($cs?->auto_renew ?? false));
        $provision->setAttribute('is_recurring', (bool) ($product?->is_recurring ?? true));
        $provision->setAttribute('recurring_period', $product?->recurring_period ?? 12);
        $provision->setAttribute('price', $cs?->renewal_price ?? $product?->price ?? 0);
        $provision->setAttribute('description', $product?->description);
        // Cho phần "Thông tin đơn hàng" hiển thị (view check quan hệ orderItems số nhiều).
        $provision->setRelation('orderItems', collect(array_filter([$provision->orderItem])));

        // File SSL admin đã upload — liệt kê để trang chi tiết hiện nút tải trực tiếp,
        // khách không phải lần sang trang "thông tin truy cập" mới thấy.
        $sslDownloads = [];
        if ($provision->provision_type === 'ssl' && $cs) {
            $sp = app(\App\Services\ServiceParameterService::class);
            $labels = [
                'certificate' => ['Certificate', 'Chứng chỉ chính cho domain (.crt)'],
                'private_key' => ['Private Key', 'Khoá riêng — tuyệt đối không chia sẻ (.key)'],
                'ca_bundle'   => ['CA Bundle', 'Chứng chỉ trung gian của nhà cung cấp (.crt)'],
            ];
            foreach ($labels as $kind => [$label, $desc]) {
                if ($sp->sslFilePath($cs, $kind)) {
                    $sslDownloads[] = ['kind' => $kind, 'label' => $label, 'desc' => $desc];
                }
            }
        }

        $service = $provision;
        return view('source.web.services.show', compact('service', 'logs', 'provisionData', 'sslDownloads'));
    }

    /**
     * Hiển thị chi tiết customer service
     */
    public function showService($id)
    {
        $service = $this->findCustomerService($id);

        return view('source.web.services.show', compact('service'));
    }

    /**
     * Hiển thị thông tin truy cập cho provision
     */
    public function provisionCredentials($id)
    {
        $provision = $this->findProvision($id);

        // Kiểm tra trạng thái provision
        if ($provision->provision_status !== 'completed') {
            return back()->with('error', 'Service chưa được kích hoạt. Vui lòng chờ hoàn tất quá trình cung cấp dịch vụ.');
        }

        // Dựng thông tin hiển thị từ provision_data theo schema (giải mã field nhạy cảm,
        // bỏ ghi chú nội bộ). Đồng bộ với dữ liệu admin nhập ở module "Dịch vụ đang chạy".
        $fields = app(\App\Services\ServiceParameterService::class)
            ->customerFieldsForProvision($provision, $provision->customerService);

        if (empty($fields)) {
            return back()->with('error', 'Thông tin truy cập chưa có sẵn. Vui lòng liên hệ bộ phận hỗ trợ.');
        }

        // Log việc truy cập credentials
        $this->logCredentialAccess($provision);

        return view('source.web.services.credentials', compact('provision', 'fields'));
    }

    /**
     * Hiển thị thông tin truy cập cho customer service
     */
    public function serviceCredentials($id)
    {
        $service = $this->findCustomerService($id);

        // Kiểm tra trạng thái service
        if (!in_array($service->service_status, ['active', 'suspended'])) {
            return back()->with('error', 'Dịch vụ không ở trạng thái có thể truy cập.');
        }

        // Kiểm tra xem có thông tin meta_data không
        $metaData = $service->meta_data ?? [];
        if (empty($metaData)) {
            return back()->with('error', 'Thông tin dịch vụ chưa có sẵn. Vui lòng liên hệ bộ phận hỗ trợ.');
        }

        return view('source.web.services.service-info', compact('service'));
    }

    /**
     * Trang báo giá gia hạn (GET) — cho phép chọn VAT + phương thức thanh toán.
     */
    public function showRenewQuote(Request $request, $id)
    {
        try {
            $customerService = $this->findOwnedCustomerService($id);
            $customerService->load(['product', 'customer']);

            if (!$this->lifecycle->currentRenewalPrice($customerService)) {
                return redirect()->route('customer.services.index')
                    ->with('error', 'Dịch vụ này chưa cấu hình giá gia hạn. Vui lòng liên hệ hỗ trợ.');
            }

            $vatInvoice = $request->boolean('vat_invoice');
            $amounts    = $this->lifecycle->computeRenewalAmounts($customerService, $vatInvoice);

            // Prefill VAT info từ invoice VAT gần nhất của khách (nếu có) để khỏi nhập lại.
            $lastVat = Invoices::where('customer_id', $customerService->customer_id)
                ->where('vat_invoice_requested', true)
                ->whereNotNull('vat_company_name')
                ->latest('id')
                ->first();

            return view('source.web.services.renew', array_merge($amounts, [
                'service' => $customerService,
                'user'    => Auth::user(),
                'config'  => Config::current(),
                'lastVat' => $lastVat,
            ]));
        } catch (\Exception $e) {
            Log::error('Show renew quote failed: ' . $e->getMessage(), ['id' => $id]);
            return redirect()->route('customer.services.index')
                ->with('error', 'Không thể hiển thị trang gia hạn. Vui lòng thử lại hoặc liên hệ hỗ trợ.');
        }
    }

    /**
     * Tải PDF báo giá gia hạn — cùng mẫu với báo giá giỏ hàng,
     * biến thể thường/VAT theo ?vat_invoice.
     */
    public function downloadRenewQuotePdf(Request $request, $id)
    {
        try {
            $customerService = $this->findOwnedCustomerService($id);
            $customerService->load('product');

            if (!$this->lifecycle->currentRenewalPrice($customerService)) {
                return redirect()->route('customer.services.index')
                    ->with('error', 'Dịch vụ này chưa cấu hình giá gia hạn. Vui lòng liên hệ hỗ trợ.');
            }

            $vatInvoice = $request->boolean('vat_invoice');
            $amounts    = $this->lifecycle->computeRenewalAmounts($customerService, $vatInvoice);

            $productName = $customerService->product->name ?? ('Dịch vụ #' . $customerService->id);
            $cycleLabel  = $customerService->billing_cycle === 'monthly' ? '1 tháng' : '1 năm';
            $detail      = 'Gia hạn dịch vụ #' . $customerService->id . ' thêm ' . $cycleLabel;
            if ($customerService->expires_at) {
                $detail .= ' | Hết hạn hiện tại: ' . $customerService->expires_at->format('d/m/Y');
            }

            $rows = [[
                'name'      => $productName . ' (Gia hạn)',
                'detail'    => $detail,
                'unit'      => 'Kỳ',
                'qty'       => 1,
                'unitPrice' => $amounts['subtotal'],
                'lineTotal' => $amounts['subtotal'],
            ]];

            $quoteNumber = 'QUOTE-REN-' . date('Ymd') . '-' . str_pad($customerService->id, 4, '0', STR_PAD_LEFT);
            $expireDate  = now()->addDays(10)->format('d/m/Y');

            $pdf = app(QuotePdfService::class)
                ->build($rows, $amounts, $vatInvoice, $quoteNumber, Auth::user(), $expireDate);

            return $pdf->download('bao-gia-gia-han-' . $customerService->id . '-' . date('Ymd') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Download renew quote PDF failed: ' . $e->getMessage(), ['id' => $id]);
            return back()->with('error', 'Không thể tạo báo giá PDF. Vui lòng thử lại sau.');
        }
    }

    /**
     * Xử lý gia hạn (POST) — nhận payment_method (wallet/bank) + VAT fields.
     */
    public function renewService(Request $request, $id)
    {
        try {
            $customerService = $this->findOwnedCustomerService($id);
            $customer        = Auth::user()->customer;

            if (!$customer) {
                return back()->with('error', 'Bạn cần cập nhật thông tin khách hàng để gia hạn.');
            }

            $vatInvoice = $request->boolean('vat_invoice');
            $vatInfo    = [];

            if ($vatInvoice) {
                $validated = $request->validate([
                    'vat_company_name'    => 'required|string|max:255',
                    'vat_tax_code'        => 'required|string|max:50|regex:/^[0-9\-]{10,14}$/',
                    'vat_company_address' => 'required|string|max:255',
                    'vat_company_email'   => 'required|email|max:255',
                ], [
                    'vat_tax_code.regex' => 'Mã số thuế phải gồm 10–14 ký tự số (cho phép dấu gạch ngang).',
                ]);
                $vatInfo = $validated;
            }

            $method  = $request->input('payment_method', 'bank');
            $amounts = $this->lifecycle->computeRenewalAmounts($customerService, $vatInvoice);
            $total   = $amounts['total'];

            // Ghi nhận lựa chọn auto_renew (tác dụng cho chu kỳ kế tiếp)
            $customerService->update([
                'auto_renew' => $request->boolean('auto_renew'),
            ]);

            // Tạo renewal order + invoice
            $created = $this->lifecycle->createRenewalOrder($customerService, $customer, $vatInvoice, $vatInfo);
            $order   = $created['order'];
            $invoice = $created['invoice'];

            if ($method === 'wallet') {
                if (!$customer->hasBalance($total)) {
                    return redirect()->route('deposit')
                        ->with('error', 'Số dư ví không đủ. Vui lòng nạp thêm hoặc chọn chuyển khoản.');
                }

                $result = app(PaymentService::class)->processWalletPayment($order, $customer);
                if ($result['success']) {
                    return redirect()->route('customer.services.index', ['tab' => 'services'])
                        ->with('success', 'Gia hạn thành công! Dịch vụ đã được cộng thêm chu kỳ.');
                }
                return back()->with('error', 'Không thể xử lý thanh toán từ ví.');
            }

            // Bank transfer
            $config = Config::current();
            $bank   = $config->bankInfo($vatInvoice);
            $result = app(PaymentService::class)->createBankTransferPayment($order, [
                'bank_name'      => $bank['name'],
                'account_number' => $bank['account_number'],
                'account_name'   => $bank['account_name'],
            ]);

            if ($result['success']) {
                return view('source.web.payment.bank_transfer', [
                    'payment'          => $result['payment'],
                    'transaction_code' => $result['transaction_code'],
                    'config'           => $config,
                    'invoice'          => $invoice,
                    'amountToPay'      => $total,
                ]);
            }

            return back()->with('error', 'Không thể tạo yêu cầu thanh toán chuyển khoản.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Renew service failed: ' . $e->getMessage(), ['id' => $id]);
            return back()->with('error', 'Không thể gia hạn dịch vụ. Vui lòng thử lại hoặc liên hệ hỗ trợ.');
        }
    }

    /**
     * Yêu cầu hủy dịch vụ
     */
    public function requestCancellation(Request $request, $id)
    {
        try {
            $customerService = $this->findOwnedCustomerService($id);
            $reason          = $request->input('reason', 'Customer request');

            $this->lifecycle->cancel($customerService, $reason);

            return back()->with('success', 'Yêu cầu hủy dịch vụ đã được ghi nhận.');
        } catch (\Exception $e) {
            Log::error('Cancel service failed: ' . $e->getMessage(), ['id' => $id]);
            return back()->with('error', 'Không thể hủy dịch vụ. Vui lòng thử lại sau.');
        }
    }

    /**
     * Tìm CustomerService của chính customer đang đăng nhập
     */
    private function findOwnedCustomerService(int $id): CustomerService
    {
        $customer = Auth::user()->customer;
        if (!$customer) {
            abort(403, 'Bạn cần cập nhật thông tin khách hàng để quản lý dịch vụ.');
        }

        // Ưu tiên tìm theo CustomerService.id
        $service = CustomerService::where('customer_id', $customer->id)
            ->with(['product', 'provision', 'customer'])
            ->find($id);

        if ($service) {
            return $service;
        }

        // Fallback: view cũ truyền Products.id (sold service) → tìm CustomerService tương ứng
        $product = Products::where('customer_id', $customer->id)
            ->whereNotNull('customer_id')
            ->find($id);

        if (!$product) {
            abort(404, 'Không tìm thấy dịch vụ để gia hạn.');
        }

        // 1) CustomerService đã backfill trước đó
        $existing = CustomerService::where('customer_id', $customer->id)
            ->where('legacy_product_id', $product->id)
            ->with(['product', 'provision', 'customer'])
            ->first();
        if ($existing) {
            return $existing;
        }

        // 2) Match theo template (parent) nếu có
        $templateId = $product->parent_product_id ?: $product->id;
        $matched = CustomerService::where('customer_id', $customer->id)
            ->where('product_id', $templateId)
            ->whereNull('legacy_product_id')
            ->with(['product', 'provision', 'customer'])
            ->latest()
            ->first();
        if ($matched) {
            return $matched;
        }

        // 3) Backfill: tạo CustomerService từ legacy Products record
        return $this->backfillCustomerServiceFromLegacy($product);
    }

    /**
     * Tạo CustomerService record cho legacy Products sold (pre-customer_services table).
     * Không sửa dữ liệu Products cũ; chỉ thêm CustomerService để lifecycle hoạt động.
     */
    private function backfillCustomerServiceFromLegacy(Products $product): CustomerService
    {
        return app(\App\Services\LegacyServiceBackfiller::class)->create($product);
    }

    /**
     * Tìm service provision của customer hiện tại
     */
    private function findProvision($id)
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            abort(403, 'Bạn cần cập nhật thông tin khách hàng để truy cập dịch vụ.');
        }

        $provision = ServiceProvision::with(['product', 'orderItem.order', 'logs'])
            ->find($id);

        // Defense-in-depth: kiểm tra ownership rõ ràng + log nếu có truy cập trái phép.
        if (!$provision || (int) $provision->customer_id !== (int) $customer->id) {
            \Illuminate\Support\Facades\Log::warning('IDOR attempt on provision', [
                'requested_id' => $id,
                'auth_user_id' => auth()->id(),
                'customer_id'  => $customer->id,
                'ip'           => request()->ip(),
            ]);
            abort(404);
        }

        return $provision;
    }

    /**
     * Tìm customer service của customer hiện tại (legacy bảng products).
     */
    private function findCustomerService($id)
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            abort(403, 'Bạn cần cập nhật thông tin khách hàng để truy cập dịch vụ.');
        }

        $product = Products::with(['parentProduct', 'orderItems.order'])
            ->whereNotNull('customer_id')
            ->find($id);

        if (!$product || (int) $product->customer_id !== (int) $customer->id) {
            \Illuminate\Support\Facades\Log::warning('IDOR attempt on legacy customer service', [
                'requested_id' => $id,
                'auth_user_id' => auth()->id(),
                'customer_id'  => $customer->id,
                'ip'           => request()->ip(),
            ]);
            abort(404);
        }

        return $product;
    }

    /**
     * Log việc truy cập credentials
     */
    private function logCredentialAccess($provision)
    {
        ProvisionLog::create([
            'provision_id' => $provision->id,
            'action' => 'credentials_viewed',
            'performed_by' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => json_encode([  // ← SỬA THÀNH notes
                'timestamp' => now(),
                'customer_id' => $provision->customer_id
            ])
        ]);
    }

    /**
     * Download SSL certificate files
     */
    public function downloadSSL($id, $type)
    {
        $provision = $this->findProvision($id);

        // Check if provision is completed and has SSL data
        if ($provision->provision_status !== 'completed') {
            return back()->with('error', 'SSL certificate chưa sẵn sàng để download.');
        }

        // Đọc file SSL theo cấu trúc lưu mới (storage private, key đã mã hóa) qua CS liên kết.
        $service = $provision->customerService;
        $sp = app(\App\Services\ServiceParameterService::class);
        $sslFiles = array_filter([
            'certificate' => $service ? $sp->readSslFile($service, 'certificate') : null,
            'private_key' => $service ? $sp->readSslFile($service, 'private_key') : null,
            'ca_bundle'   => $service ? $sp->readSslFile($service, 'ca_bundle') : null,
        ], fn ($v) => $v !== null && $v !== '');

        if (empty($sslFiles)) {
            return back()->with('error', 'Không tìm thấy files SSL certificate.');
        }

        // Log download activity
        $this->logSSLDownload($provision, $type);

        return match ($type) {
            'certificate' => $this->downloadCertificate($sslFiles, $provision),
            'private_key' => $this->downloadPrivateKey($sslFiles, $provision),
            'ca_bundle' => $this->downloadCABundle($sslFiles, $provision),
            'all' => $this->downloadAllSSLFiles($sslFiles, $provision),
            default => abort(404)
        };
    }

    /**
     * Download certificate file
     */
    private function downloadCertificate($sslFiles, $provision)
    {
        if (!isset($sslFiles['certificate'])) {
            abort(404, 'Certificate file not found');
        }

        $filename = $this->getSSLFilename($provision, 'certificate.crt');

        return response($sslFiles['certificate'])
            ->header('Content-Type', 'application/x-pem-file')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Download private key file
     */
    private function downloadPrivateKey($sslFiles, $provision)
    {
        if (!isset($sslFiles['private_key'])) {
            abort(404, 'Private key file not found');
        }

        $filename = $this->getSSLFilename($provision, 'private.key');

        return response($sslFiles['private_key'])
            ->header('Content-Type', 'application/x-pem-file')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Download CA Bundle file
     */
    private function downloadCABundle($sslFiles, $provision)
    {
        if (!isset($sslFiles['ca_bundle'])) {
            abort(404, 'CA Bundle file not found');
        }

        $filename = $this->getSSLFilename($provision, 'ca_bundle.crt');

        return response($sslFiles['ca_bundle'])
            ->header('Content-Type', 'application/x-pem-file')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Download all SSL files as tar.gz (không cần ZipArchive)
     */
    private function downloadAllSSLFiles($sslFiles, $provision)
    {
        $tempDir = sys_get_temp_dir() . '/ssl_' . uniqid();
        mkdir($tempDir, 0750, true);

        $tarPath = $tempDir . '.tar';
        $gzPath  = $tarPath . '.gz';

        try {
            $fileMap = [
                'certificate' => $this->getSSLFilename($provision, 'certificate.crt'),
                'private_key' => $this->getSSLFilename($provision, 'private.key'),
                'ca_bundle'   => $this->getSSLFilename($provision, 'ca_bundle.crt'),
            ];

            $phar = new \PharData($tarPath);

            foreach ($fileMap as $key => $name) {
                if (isset($sslFiles[$key])) {
                    $filePath = $tempDir . '/' . $name;
                    file_put_contents($filePath, $sslFiles[$key]);
                    $phar->addFile($filePath, $name);
                }
            }

            $instructions = $this->generateSSLInstructions($provision);
            $instrPath = $tempDir . '/INSTALLATION_INSTRUCTIONS.txt';
            file_put_contents($instrPath, $instructions);
            $phar->addFile($instrPath, 'INSTALLATION_INSTRUCTIONS.txt');

            $phar->compress(\Phar::GZ);
            unset($phar);

            // Dọn temp dir và file .tar (giữ lại .tar.gz)
            foreach (glob($tempDir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($tempDir);
            @unlink($tarPath);

            $downloadName = $this->getSSLFilename($provision, 'ssl_files.tar.gz');

            // Set sẵn Content-Type để BinaryFileResponse khỏi tự đoán MIME bằng finfo
            // (extension fileinfo không có trên VPS).
            return response()->download($gzPath, $downloadName, [
                'Content-Type' => 'application/gzip',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            foreach (glob($tempDir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($tempDir);
            @unlink($tarPath);
            @unlink($gzPath);

            Log::error('SSL file download failed: ' . $e->getMessage(), ['provision_id' => $provision->id ?? null]);
            abort(500, 'Không thể tạo file tải xuống. Vui lòng thử lại hoặc liên hệ hỗ trợ.');
        }
    }

    /**
     * Generate SSL filename with domain
     */
    private function getSSLFilename($provision, $defaultName)
    {
        $provisionData = json_decode($provision->provision_data, true) ?? [];
        $domain = $provisionData['domain'] ?? 'ssl';

        // Clean domain for filename
        $cleanDomain = preg_replace('/[^a-z0-9\-\.]/', '', strtolower($domain));

        $ext = pathinfo($defaultName, PATHINFO_EXTENSION);
        $name = pathinfo($defaultName, PATHINFO_FILENAME);

        return "{$cleanDomain}_{$name}.{$ext}";
    }

    /**
     * Generate installation instructions
     */
    private function generateSSLInstructions($provision)
    {
        $provisionData = json_decode($provision->provision_data, true) ?? [];
        $domain = $provisionData['domain'] ?? 'your-domain.com';

        return "SSL Certificate Installation Instructions
    
Domain: {$domain}
Generated: " . now()->format('Y-m-d H:i:s') . "

Files included:
- certificate.crt: Your SSL certificate
- private.key: Your private key (keep this secure!)
- ca_bundle.crt: Certificate authority bundle

Installation steps:
1. Upload all files to your server
2. Configure your web server (Apache/Nginx) to use these files
3. Test your SSL installation

For detailed instructions, contact our support team.

IMPORTANT: Keep your private key secure and never share it!
";
    }

    /**
     * Log SSL download activity
     */
    private function logSSLDownload($provision, $type)
    {
        ProvisionLog::create([
            'provision_id' => $provision->id,
            'action' => 'ssl_download',
            'performed_by' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => json_encode([
                'download_type' => $type,
                'timestamp' => now(),
                'customer_id' => $provision->customer_id
            ]),
        ]);
    }
}
