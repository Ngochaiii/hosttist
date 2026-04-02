<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{ServiceProvision, ProvisionLog, Products, CustomerService};
use App\Services\{ProvisionService, ServiceLifecycleService};
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
        Log::info('Customer services debug', [
            'user_id' => auth()->id(),
            'customer_id' => $customer->id ?? null,
            'has_customer' => !is_null($customer)
        ]);
        if (!$customer) {
            return redirect()->route('customer.profile')
                ->with('error', 'Vui lòng cập nhật thông tin khách hàng để xem dịch vụ.');
        }

        // Get ServiceProvisions (records trong bảng service_provisions)
        $provisionQuery = ServiceProvision::where('customer_id', $customer->id)
            ->with(['product', 'orderItem.order']);

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
    
    // DEBUG: Kiểm tra provision data
    $provisionData = json_decode($provision->provision_data, true) ?? [];
    Log::info('Provision data debug', [
        'provision_id' => $provision->id,
        'provision_data' => $provisionData,
        'credentials' => $provisionData['credentials'] ?? 'not found'
    ]);
    $service = $provision;
    return view('source.web.services.show', compact('service', 'logs', 'provisionData'));
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

        // Kiểm tra xem có credentials không
        $provisionData = json_decode($provision->provision_data, true) ?? [];
        if (empty($provisionData['credentials'])) {
            return back()->with('error', 'Thông tin truy cập chưa có sẵn. Vui lòng liên hệ bộ phận hỗ trợ.');
        }

        // Log việc truy cập credentials
        $this->logCredentialAccess($provision);

        return view('source.web.services.credentials', compact('provision'));
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
     * Gia hạn dịch vụ qua CustomerService + ServiceLifecycleService
     */
    public function renewService(Request $request, $id)
    {
        try {
            $customerService = $this->findOwnedCustomerService($id);
            $customer        = Auth::user()->customer;

            $result = $this->lifecycle->renew($customerService, $customer);

            if ($result['success']) {
                return back()->with('success', 'Gia hạn thành công đến ' . $result['new_expiry']->format('d/m/Y') . '!');
            }

            if (!empty($result['need_deposit'])) {
                return back()->with('error', 'Số dư ví không đủ. Vui lòng nạp thêm ít nhất ' . number_format($result['required_amount'], 0, ',', '.') . ' đ.');
            }

            return back()->with('error', $result['error'] ?? 'Không thể gia hạn dịch vụ.');
        } catch (\Exception $e) {
            Log::error('Renew service failed: ' . $e->getMessage(), ['id' => $id]);
            return back()->with('error', 'Lỗi gia hạn: ' . $e->getMessage());
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
            return back()->with('error', 'Không thể hủy dịch vụ: ' . $e->getMessage());
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

        return CustomerService::where('customer_id', $customer->id)
            ->with(['product', 'provision', 'customer'])
            ->findOrFail($id);
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

        return ServiceProvision::where('customer_id', $customer->id)
            ->with(['product', 'orderItem.order', 'logs'])
            ->findOrFail($id);
    }

    /**
     * Tìm customer service của customer hiện tại
     */
    private function findCustomerService($id)
    {
        $customer = auth()->user()->customer;

        if (!$customer) {
            abort(403, 'Bạn cần cập nhật thông tin khách hàng để truy cập dịch vụ.');
        }

        return Products::where('customer_id', $customer->id)
            ->whereNotNull('customer_id')
            ->with(['parentProduct', 'orderItems.order'])
            ->findOrFail($id);
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

        $provisionData = json_decode($provision->provision_data, true) ?? [];

        if (!isset($provisionData['ssl_files'])) {
            return back()->with('error', 'Không tìm thấy files SSL certificate.');
        }

        $sslFiles = $provisionData['ssl_files'];

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

            return response()->download($gzPath, $downloadName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            foreach (glob($tempDir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($tempDir);
            @unlink($tarPath);
            @unlink($gzPath);

            abort(500, 'Không thể tạo file tải xuống: ' . $e->getMessage());
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
