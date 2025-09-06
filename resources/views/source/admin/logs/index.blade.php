{{-- resources/views/source/admin/logs/index.blade.php --}}
@extends('layouts.admin.index')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-body">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Audit Logs</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Provision Audit Logs</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-2">
                                    <input type="number" name="provision_id" class="form-control" 
                                           placeholder="Provision ID" value="{{ request('provision_id') }}">
                                </div>
                                <div class="col-md-2">
                                    <select name="action" class="form-control">
                                        <option value="">-- Action --</option>
                                        <option value="form_accessed" {{ request('action') == 'form_accessed' ? 'selected' : '' }}>Form Accessed</option>
                                        <option value="form_submitted" {{ request('action') == 'form_submitted' ? 'selected' : '' }}>Form Submitted</option>
                                        <option value="status_changed" {{ request('action') == 'status_changed' ? 'selected' : '' }}>Status Changed</option>
                                        <option value="provision_updated" {{ request('action') == 'provision_updated' ? 'selected' : '' }}>Updated</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="severity" class="form-control">
                                        <option value="">-- Severity --</option>
                                        <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                                        <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                                        <option value="error" {{ request('severity') == 'error' ? 'selected' : '' }}>Error</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                        </form>

                        <!-- Logs Table -->
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Provision</th>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Severity</th>
                                        <th>IP</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                    <tr>
                                        <td>
                                            <small>{{ $log->created_at->format('H:i:s') }}</small><br>
                                            <small class="text-muted">{{ $log->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.provisions.show', $log->provision_id) }}" class="text-primary">
                                                #{{ $log->provision_id }}
                                            </a>
                                            @if($log->provision)
                                            <br><small class="text-muted">{{ $log->provision->provision_type }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $log->getSeverityColor() }}">
                                                {{ $log->getActionLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($log->performedBy)
                                            {{ $log->performedBy->name }}
                                            @else
                                            <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $log->getSeverityColor() }}">
                                                {{ ucfirst($log->severity) }}
                                            </span>
                                        </td>
                                        <td><small>{{ $log->ip_address }}</small></td>
                                        <td>
                                            @if($log->additional_data)
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    data-toggle="modal" data-target="#logModal{{ $log->id }}">
                                                View
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        {{ $logs->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modals for log details -->
@foreach($logs as $log)
@if($log->additional_data)
<div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Log Details - {{ $log->getActionLabel() }}</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Time:</strong> {{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                <p><strong>User:</strong> {{ $log->performedBy->name ?? 'System' }}</p>
                <p><strong>IP:</strong> {{ $log->ip_address }}</p>
                <p><strong>User Agent:</strong> {{ $log->user_agent }}</p>
                
                @if($log->error_message)
                <div class="alert alert-danger">
                    <strong>Error:</strong> {{ $log->error_message }}
                </div>
                @endif
                
                <h6>Additional Data:</h6>
                <pre class="bg-light p-3">{{ json_encode($log->additional_data, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection

{{-- resources/views/source/admin/provisions/show.blade.php - Add logs section --}}
{{-- Add this section to your existing show.blade.php --}}

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Activity Logs</h3>
            </div>
            <div class="card-body">
                @if($provision->logs->count() > 0)
                <div class="timeline">
                    @foreach($provision->logs->take(10) as $log)
                    <div class="time-label">
                        <span class="bg-{{ $log->getSeverityColor() }}">
                            {{ $log->created_at->format('H:i') }}
                        </span>
                    </div>
                    <div>
                        <i class="fas fa-{{ $log->severity == 'error' ? 'exclamation-triangle' : 'info-circle' }} bg-{{ $log->getSeverityColor() }}"></i>
                        <div class="timeline-item">
                            <span class="time">
                                <i class="fas fa-clock"></i> {{ $log->created_at->diffForHumans() }}
                            </span>
                            <h3 class="timeline-header">
                                {{ $log->getActionLabel() }}
                                @if($log->performedBy)
                                by {{ $log->performedBy->name }}
                                @endif
                            </h3>
                            <div class="timeline-body">
                                @if($log->additional_data)
                                @if(isset($log->additional_data['old_status']) && isset($log->additional_data['new_status']))
                                Status changed from <strong>{{ $log->additional_data['old_status'] }}</strong> 
                                to <strong>{{ $log->additional_data['new_status'] }}</strong>
                                @if(isset($log->additional_data['reason']))
                                <br>Reason: {{ $log->additional_data['reason'] }}
                                @endif
                                @elseif(isset($log->additional_data['form_type']))
                                Accessed {{ $log->additional_data['form_type'] }} form
                                @elseif(isset($log->additional_data['action_type']))
                                Form submitted with action: {{ $log->additional_data['action_type'] }}
                                @endif
                                @endif
                                
                                @if($log->error_message)
                                <div class="alert alert-danger alert-sm mt-2">
                                    {{ $log->error_message }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if($provision->logs->count() > 10)
                <div class="text-center mt-3">
                    <a href="{{ route('admin.logs.index', ['provision_id' => $provision->id]) }}" 
                       class="btn btn-sm btn-outline-primary">
                        View All Logs ({{ $provision->logs->count() }})
                    </a>
                </div>
                @endif
                @else
                <p class="text-muted">No activity logs found.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Routes to add in web.php --}}
{{-- 

--}}