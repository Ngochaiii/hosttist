<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\Domain;
use App\Models\DomainTld;
use App\Services\DomainAvailabilityService;
use App\Services\DomainCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Quản lý kho tên miền: danh sách + báo cáo lãi + import domain đã mua sẵn từ Nhân Hòa.
 */
class DomainController extends Controller
{
    public function __construct(private DomainCatalogService $catalog) {}

    public function index(Request $request)
    {
        $query = Domain::query()->with(['customer.user', 'tldRef']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where('domain_name', 'like', '%' . $request->q . '%');
        }

        $domains = $query->latest()->paginate(20)->withQueryString();

        // Báo cáo lãi.
        $stats = [
            'total'        => Domain::count(),
            'active'       => Domain::where('status', Domain::STATUS_ACTIVE)->count(),
            'profit_all'   => (float) Domain::sum('profit'),
            'profit_month' => (float) Domain::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('profit'),
        ];

        return view('source.admin.domains.index', compact('domains', 'stats'));
    }

    public function create()
    {
        $tlds      = DomainTld::active()->orderBy('tld')->get();
        $customers = Customers::with('user')->get();

        return view('source.admin.domains.create', compact('tlds', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain_name'   => ['required', 'string', 'max:255', 'unique:domains,domain_name'],
            'customer_id'   => ['nullable', 'exists:customers,id'],
            'cost_price'    => ['required', 'numeric', 'min:0'],
            'sell_price'    => ['nullable', 'numeric', 'min:0'],
            'years'         => ['required', 'integer', 'min:1', 'max:10'],
            'registered_at' => ['nullable', 'date'],
            'expires_at'    => ['nullable', 'date', 'after:registered_at'],
            'status'        => ['required', 'in:pending,active,expired,cancelled,transferred'],
            'auth_code'     => ['nullable', 'string', 'max:255'],
            'nameservers'   => ['nullable', 'string'],
            'auto_renew'    => ['nullable'],
            'notes'         => ['nullable', 'string'],
            // Chủ thể
            'reg_name'      => ['nullable', 'string', 'max:255'],
            'reg_email'     => ['nullable', 'email', 'max:255'],
            'reg_phone'     => ['nullable', 'string', 'max:30'],
            'reg_address'   => ['nullable', 'string', 'max:500'],
            'reg_id_type'   => ['nullable', 'in:cccd,passport,tax_code'],
            'reg_id_number' => ['nullable', 'string', 'max:50'],
        ]);

        // Gom thông tin chủ thể (sẽ được model mã hóa).
        $registrant = array_filter([
            'name'      => $validated['reg_name'] ?? null,
            'email'     => $validated['reg_email'] ?? null,
            'phone'     => $validated['reg_phone'] ?? null,
            'address'   => $validated['reg_address'] ?? null,
            'id_type'   => $validated['reg_id_type'] ?? null,
            'id_number' => $validated['reg_id_number'] ?? null,
        ], fn($v) => $v !== null && $v !== '');

        // Nameservers: mỗi dòng 1 NS.
        $nameservers = $request->filled('nameservers')
            ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $request->nameservers))))
            : null;

        try {
            $domain = $this->catalog->importExisting([
                'domain_name'   => $validated['domain_name'],
                'customer_id'   => $validated['customer_id'] ?? null,
                'cost_price'    => $validated['cost_price'],
                'sell_price'    => $validated['sell_price'] ?? null,
                'years'         => $validated['years'],
                'registered_at' => $validated['registered_at'] ?? null,
                'expires_at'    => $validated['expires_at'] ?? null,
                'status'        => $validated['status'],
                'registrant'    => $registrant ?: null,
                'auth_code'     => $validated['auth_code'] ?? null,
                'nameservers'   => $nameservers,
                'auto_renew'    => $request->boolean('auto_renew'),
                'notes'         => $validated['notes'] ?? null,
            ]);

            return redirect()->route('admin.domains.index')
                ->with('success', 'Đã import tên miền ' . $domain->domain_name . ' (lãi ' . number_format($domain->profit) . 'đ).');
        } catch (\Exception $e) {
            Log::error('Domain import failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Không thể import tên miền. Vui lòng kiểm tra log hoặc thử lại.']);
        }
    }

    /** AJAX: kiểm tra tên miền còn trống (RDAP). */
    public function checkAvailability(Request $request, DomainAvailabilityService $availability): JsonResponse
    {
        $request->validate(['domain' => ['required', 'string', 'max:255']]);

        return response()->json($availability->check($request->domain));
    }

    public function show($id)
    {
        $domain = Domain::with(['customer.user', 'tldRef'])->findOrFail($id);

        return view('source.admin.domains.show', compact('domain'));
    }

    public function destroy($id)
    {
        $domain = Domain::findOrFail($id);
        $domain->delete();

        return back()->with('success', 'Đã xoá tên miền ' . $domain->domain_name);
    }
}
