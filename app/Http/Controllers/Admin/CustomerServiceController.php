<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerService;
use App\Models\Domain;
use App\Services\ServiceParameterSchema;
use App\Services\ServiceParameterService;
use Illuminate\Http\Request;

/**
 * Admin: Quản lý dịch vụ ĐANG CHẠY của toàn bộ khách hàng.
 * Sửa thông số kỹ thuật (lưu vào provision_data), đổi mật khẩu VPS, upload file SSL.
 * KHÔNG đụng tới luồng tiền — đổi trạng thái chỉ cập nhật cột status.
 */
class CustomerServiceController extends Controller
{
    public function __construct(private ServiceParameterService $params)
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $query = CustomerService::with(['customer', 'product', 'provision']);

        if ($request->filled('searchText')) {
            $text = $request->searchText;
            $query->whereHas('customer', fn ($q) => $q->where('name', 'LIKE', "%{$text}%")
                ->orWhere('email', 'LIKE', "%{$text}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $type = $request->type;
            $query->where(function ($w) use ($type) {
                $w->whereHas('provision', fn ($p) => $p->where('provision_type', $type))
                  ->orWhereHas('product', fn ($p) => $p->where('type', $type));
            });
        }

        if ($request->boolean('expiring')) {
            $query->expiringSoon(30);
        }

        $services = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $stats = [
            'active'    => CustomerService::active()->count(),
            'expiring'  => CustomerService::expiringSoon(30)->count(),
            'suspended' => CustomerService::where('status', 'suspended')->count(),
            'total'     => CustomerService::count(),
        ];

        $types = ServiceParameterSchema::MANAGED_TYPES;

        return view('source.admin.services.index', compact('services', 'stats', 'types'));
    }

    public function show($id)
    {
        $service = CustomerService::with(['customer', 'product', 'provision.logs'])->findOrFail($id);

        $schema = $this->params->schema($service);
        $values = $service->provision ? $this->params->displayValues($service) : [];
        $logs   = $service->provision?->logs?->sortByDesc('created_at') ?? collect();

        return view('source.admin.services.show', compact('service', 'schema', 'values', 'logs'));
    }

    public function edit($id)
    {
        $service = CustomerService::with(['customer', 'product', 'provision'])->findOrFail($id);

        // Domain có module kho riêng — chuyển sang đó.
        if ($service->service_type === 'domain') {
            $domain = Domain::where('customer_service_id', $service->id)->first();
            return $domain
                ? redirect()->route('admin.domains.show', $domain->id)
                : redirect()->route('admin.domains.index')
                    ->with('info', 'Quản lý tên miền tại module Kho tên miền.');
        }

        $schema = $this->params->schema($service);
        $values = $service->provision ? $this->params->displayValues($service) : [];

        return view('source.admin.services.edit', compact('service', 'schema', 'values'));
    }

    public function update(Request $request, $id)
    {
        $service = CustomerService::with('provision')->findOrFail($id);

        if ($service->service_type === 'domain') {
            return redirect()->route('admin.services.show', $id);
        }

        $rules = [
            'started_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:started_at',
        ];
        if ($service->provision) {
            $rules += $this->params->rules($service->service_type);
        }
        $data = $request->validate($rules);

        // Vòng đời (cột trên customer_services) — sửa được kể cả dịch vụ legacy.
        $service->update([
            'started_at' => $data['started_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Thông số kỹ thuật → provision_data (chỉ khi có provision liên kết).
        if ($service->provision) {
            $this->params->applyUpdate($service, $request);
        }

        return redirect()->route('admin.services.show', $id)
            ->with('success', 'Đã cập nhật dịch vụ.');
    }

    public function updateStatus(Request $request, $id)
    {
        $service = CustomerService::findOrFail($id);
        $data = $request->validate([
            'status' => 'required|in:active,suspended,cancelled',
            'reason' => 'nullable|string|max:500',
        ]);

        $service->update([
            'status' => $data['status'],
            'notes'  => $data['reason'] ?: $service->notes,
        ]);

        return back()->with('success', 'Đã đổi trạng thái dịch vụ thành "' . $service->status_label . '".');
    }

    public function downloadSslFile($id, string $kind)
    {
        $service = CustomerService::with('provision')->findOrFail($id);

        abort_unless(in_array($kind, ['certificate', 'private_key', 'ca_bundle'], true), 404);

        $content = $this->params->readSslFile($service, $kind);
        abort_if($content === null, 404, 'Không tìm thấy file.');

        $ext      = $kind === 'private_key' ? 'key' : 'crt';
        $domain   = ($this->params->rawData($service)['domain'] ?? 'ssl') ?: 'ssl';
        $filename = "{$domain}-{$kind}.{$ext}";

        return response($content, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
