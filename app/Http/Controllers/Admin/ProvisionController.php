<?php
// app/Http/Controllers/Admin/ProvisionController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvision;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ProvisionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(Request $request)
    {
        $query = ServiceProvision::with(['customer', 'product', 'orderItem']);

        // Search
        if ($request->filled('searchText')) {
            $searchText = $request->searchText;
            $searchBy = $request->get('searchBy', 'customer');

            if ($searchBy == 'customer') {
                $query->whereHas('customer', function ($q) use ($searchText) {
                    $q->where('name', 'LIKE', "%{$searchText}%");
                });
            } elseif ($searchBy == 'product') {
                $query->whereHas('product', function ($q) use ($searchText) {
                    $q->where('name', 'LIKE', "%{$searchText}%");
                });
            } elseif ($searchBy == 'id') {
                $query->where('id', $searchText);
            }
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('provision_status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('provision_type', $request->type);
        }

        // Sorting
        $sortBy = $request->get('sortBy', 'id');
        $orderBy = $request->get('orderBy', 'desc');
        $query->orderBy($sortBy, $orderBy);

        $provisions = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => ServiceProvision::count(),
            'pending' => ServiceProvision::where('provision_status', 'pending')->count(),
            'processing' => ServiceProvision::where('provision_status', 'processing')->count(),
            'completed' => ServiceProvision::where('provision_status', 'completed')->count(),
            'failed' => ServiceProvision::where('provision_status', 'failed')->count(),
        ];

        return view('source.admin.provisions.index', compact('provisions', 'stats'));
    }

    public function pending(Request $request)
    {
        $query = ServiceProvision::where('provision_status', 'pending')
            ->with(['customer', 'product', 'orderItem'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc');

        if ($request->filled('priority')) {
            if ($request->priority === 'high') {
                $query->where('priority', '>=', 8);
            }
        }

        if ($request->filled('type')) {
            $query->where('provision_type', $request->type);
        }

        $provisions = $query->paginate(20);

        $pendingStats = [
            'total_pending' => ServiceProvision::where('provision_status', 'pending')->count(),
            'high_priority' => ServiceProvision::where('provision_status', 'pending')
                ->where('priority', '>=', 8)->count(),
            'overdue' => ServiceProvision::where('provision_status', 'pending')
                ->where('estimated_completion', '<', now())
                ->count(),
            'today' => ServiceProvision::where('provision_status', 'pending')
                ->whereDate('created_at', today())->count()
        ];

        return view('source.admin.provisions.pending', compact('provisions', 'pendingStats'));
    }

    public function show($id)
    {
        $provision = ServiceProvision::with([
            'customer',
            'product',
            'orderItem',
            'logs' => function ($query) {
                $query->latest();
            }
        ])->findOrFail($id);

        // Mark as viewed & log
        $provision->increment('view_count');
        $provision->update(['last_viewed_at' => now()]);
        
        AuditLogService::log($id, 'viewed', [
            'view_count' => $provision->view_count
        ]);

        return view('source.admin.provisions.show', compact('provision'));
    }

    public function showForm($id)
    {
        $provision = ServiceProvision::with(['customer', 'product', 'orderItem'])
            ->findOrFail($id);
        
        // Log form access
        AuditLogService::logFormAccess($id, $provision->provision_type);
        
        if (!in_array($provision->provision_status, ['pending', 'processing'])) {
            AuditLogService::logError($id, 'form_access_denied', 'Invalid status for form access', [
                'current_status' => $provision->provision_status
            ]);
            
            return redirect()->route('admin.provisions.show', $id)
                ->with('error', 'Chỉ có thể chỉnh sửa provision đang chờ xử lý.');
        }

        $formView = match($provision->provision_type) {
            'ssl' => 'source.admin.provisions.forms.ssl',
            'domain' => 'source.admin.provisions.forms.domain', 
            'hosting' => 'source.admin.provisions.forms.hosting',
            default => 'source.admin.provisions.forms.hosting'
        };

        return view($formView, compact('provision'));
    }

    public function update(Request $request, $id)
    {
        $provision = ServiceProvision::findOrFail($id);
        $oldStatus = $provision->provision_status;
        
        // Validate
        $rules = $this->getValidationRules($provision->provision_type);
        
        try {
            $request->validate($rules);
        } catch (\Exception $e) {
            AuditLogService::logError($id, 'validation_failed', $e->getMessage(), [
                'form_data' => $request->except(['password', 'private_key'])
            ]);
            throw $e;
        }

        // Log form submission
        AuditLogService::logFormSubmit($id, $request->action, $request->all());

        // Update provision data
        $provisionData = json_decode($provision->provision_data, true) ?: [];
        $newData = $this->processFormData($request, $provision->provision_type, $provisionData);
        
        $provision->update([
            'provision_data' => json_encode($newData),
            'provision_notes' => $request->provision_notes,
        ]);

        // Handle completion
        if ($request->action === 'complete') {
            $provision->update([
                'provision_status' => 'completed',
                'provisioned_by' => auth()->id(),
                'provisioned_at' => now(),
                'delivery_status' => 'delivered',
                'delivered_at' => now()
            ]);
            
            AuditLogService::logStatusChange($id, $oldStatus, 'completed', 'Completed via form');
            
            return redirect()->route('admin.provisions.show', $id)
                ->with('success', 'Provision completed successfully!');
        }

        AuditLogService::log($id, 'provision_updated', [
            'fields_updated' => array_keys($request->except(['_token', '_method'])),
            'action' => 'save'
        ]);

        return redirect()->back()->with('success', 'Provision updated successfully!');
    }

    public function startProcessing($id)
    {
        $provision = ServiceProvision::findOrFail($id);
        $oldStatus = $provision->provision_status;

        if ($provision->provision_status !== 'pending') {
            AuditLogService::logError($id, 'start_processing_failed', 'Invalid status', [
                'current_status' => $provision->provision_status
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể bắt đầu xử lý provision đang chờ.'
            ]);
        }

        $provision->update(['provision_status' => 'processing']);
        
        AuditLogService::logStatusChange($id, $oldStatus, 'processing', 'Started processing');

        return response()->json([
            'success' => true,
            'message' => 'Đã bắt đầu xử lý provision.'
        ]);
    }

    public function complete(Request $request, $id)
    {
        $provision = ServiceProvision::findOrFail($id);
        $oldStatus = $provision->provision_status;

        if (!in_array($provision->provision_status, ['pending', 'processing'])) {
            AuditLogService::logError($id, 'complete_failed', 'Invalid status', [
                'current_status' => $provision->provision_status
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Provision không thể hoàn thành.'
            ]);
        }

        $provision->update([
            'provision_status' => 'completed',
            'provisioned_by' => auth()->id(),
            'provisioned_at' => now(),
            'delivery_status' => 'delivered',
            'delivered_at' => now()
        ]);

        AuditLogService::logStatusChange($id, $oldStatus, 'completed', 'Completed via quick action');

        return response()->json([
            'success' => true,
            'message' => 'Hoàn thành provision thành công!'
        ]);
    }

    public function fail(Request $request, $id)
    {
        $request->validate(['failure_reason' => 'required|string']);

        $provision = ServiceProvision::findOrFail($id);
        $oldStatus = $provision->provision_status;

        $provision->update([
            'provision_status' => 'failed',
            'failure_reason' => $request->failure_reason
        ]);

        AuditLogService::logStatusChange($id, $oldStatus, 'failed', $request->failure_reason);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu provision thất bại.'
        ]);
    }

    public function retry($id)
    {
        $provision = ServiceProvision::findOrFail($id);
        $oldStatus = $provision->provision_status;

        if ($provision->provision_status !== 'failed') {
            AuditLogService::logError($id, 'retry_failed', 'Invalid status', [
                'current_status' => $provision->provision_status
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể thử lại provision đã thất bại.'
            ]);
        }

        $provision->update([
            'provision_status' => 'pending',
            'failure_reason' => null
        ]);

        AuditLogService::logStatusChange($id, $oldStatus, 'pending', 'Retried after failure');

        return response()->json([
            'success' => true,
            'message' => 'Đã thử lại provision.'
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $request->validate(['cancel_reason' => 'required|string']);

        $provision = ServiceProvision::findOrFail($id);
        $oldStatus = $provision->provision_status;

        if ($provision->provision_status === 'completed') {
            AuditLogService::logError($id, 'cancel_failed', 'Cannot cancel completed provision');
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy provision đã hoàn thành.'
            ]);
        }

        $provision->update(['provision_status' => 'cancelled']);

        AuditLogService::logStatusChange($id, $oldStatus, 'cancelled', $request->cancel_reason);

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy provision.'
        ]);
    }

    private function getValidationRules($provisionType)
    {
        return match ($provisionType) {
            'ssl' => [
                'certificate' => 'required|string',
                'private_key' => 'required|string',
                'ca_bundle' => 'nullable|string',
                'ssl_provider' => 'nullable|string'
            ],
            'domain' => [
                'domain_name' => 'required|string',
                'registrar' => 'nullable|string',
                'expiry_date' => 'nullable|date',
                'nameservers' => 'nullable|string',
                'admin_email' => 'nullable|email',
                'auto_renewal' => 'nullable|boolean'
            ],
            'hosting' => [
                'server_name' => 'nullable|string',
                'server_ip' => 'nullable|ip',
                'control_panel' => 'nullable|string',
                'username' => 'required|string',
                'password' => 'required|string',
                'ftp_details' => 'nullable|string',
                'disk_space' => 'nullable|numeric',
                'bandwidth' => 'nullable|numeric'
            ],
            default => []
        };
    }

    private function processFormData(Request $request, $provisionType, $currentData)
    {
        switch ($provisionType) {
            case 'ssl':
                return array_merge($currentData, [
                    'certificate' => $request->certificate,
                    'private_key' => encrypt($request->private_key),
                    'ca_bundle' => $request->ca_bundle,
                    'ssl_provider' => $request->ssl_provider,
                    'updated_at' => now()->toISOString()
                ]);

            case 'domain':
                return array_merge($currentData, [
                    'domain_name' => $request->domain_name,
                    'registrar' => $request->registrar,
                    'expiry_date' => $request->expiry_date,
                    'nameservers' => array_filter(explode("\n", $request->nameservers ?: '')),
                    'admin_email' => $request->admin_email,
                    'auto_renewal' => $request->boolean('auto_renewal'),
                    'updated_at' => now()->toISOString()
                ]);

            case 'hosting':
                return array_merge($currentData, [
                    'server_name' => $request->server_name,
                    'server_ip' => $request->server_ip,
                    'control_panel' => $request->control_panel,
                    'username' => $request->username,
                    'password' => encrypt($request->password),
                    'ftp_details' => $request->ftp_details,
                    'disk_space' => $request->disk_space,
                    'bandwidth' => $request->bandwidth,
                    'updated_at' => now()->toISOString()
                ]);

            default:
                return $currentData;
        }
    }
}