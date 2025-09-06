<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProvisionLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = ProvisionLog::with(['provision', 'performedBy'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->filled('provision_id')) {
            $query->where('provision_id', $request->provision_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('user_id')) {
            $query->where('performed_by', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);

        return view('source.admin.logs.index', compact('logs'));
    }

    public function show($id)
    {
        $log = ProvisionLog::with(['provision', 'performedBy'])->findOrFail($id);
        return view('source.admin.logs.show', compact('log'));
    }
}
