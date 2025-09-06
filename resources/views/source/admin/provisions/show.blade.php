{{-- resources/views/admin/provisions/show.blade.php --}}
@extends('layouts.admin.index')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <!-- Breadcrumb -->
                <div class="card mb-3">
                    <div class="card-body">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.provisions.index') }}">Provisions</a></li>
                            <li class="breadcrumb-item active">Chi tiết #{{ $provision->id }}</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Main Info -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Thông tin Provision #{{ $provision->id }}</h3>
                        <div class="card-tools">
                            @if($provision->isPending())
                                <button type="button" class="btn btn-success btn-sm" onclick="startProcessing({{ $provision->id }})">
                                    <i class="fas fa-play"></i> Bắt đầu xử lý
                                </button>
                            @endif
                            
                            @if($provision->isPending() || $provision->isProcessing())
                                <a href="{{ route('admin.provisions.form', $provision->id) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Chỉnh sửa
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Khách hàng:</strong>
                                <p>{{ $provision->customer->name ?? 'N/A' }}
                                @if($provision->customer && $provision->customer->user)
                                    <br><small class="text-muted">{{ $provision->customer->user->email }}</small>
                                @endif
                                </p>

                                <strong>Sản phẩm:</strong>
                                <p>{{ $provision->product->name ?? 'N/A' }}
                                @if($provision->product && $provision->product->sku)
                                    <br><small class="text-muted">SKU: {{ $provision->product->sku }}</small>
                                @endif
                                </p>

                                <strong>Order Item:</strong>
                                <p>
                                @if($provision->orderItem)
                                    #{{ $provision->orderItem->id }}
                                    @if($provision->orderItem->order)
                                        (Order #{{ $provision->orderItem->order->id }})
                                    @endif
                                @else
                                    N/A
                                @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <strong>Trạng thái:</strong>
                                <p>
                                    @switch($provision->provision_status)
                                        @case('pending')
                                            <span class="badge badge-warning">Đang chờ</span>
                                            @break
                                        @case('processing')
                                            <span class="badge badge-info">Đang xử lý</span>
                                            @break
                                        @case('completed')
                                            <span class="badge badge-success">Hoàn thành</span>
                                            @break
                                        @case('failed')
                                            <span class="badge badge-danger">Thất bại</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge badge-secondary">Đã hủy</span>
                                            @break
                                    @endswitch
                                </p>

                                <strong>Loại:</strong>
                                <p>
                                    @switch($provision->provision_type)
                                        @case('digital')
                                            <span class="badge badge-primary">Kỹ thuật số</span>
                                            @break
                                        @case('physical')
                                            <span class="badge badge-info">Vật lý</span>
                                            @break
                                        @case('service')
                                            <span class="badge badge-success">Dịch vụ</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ $provision->provision_type }}</span>
                                    @endswitch
                                </p>

                                <strong>Ưu tiên:</strong>
                                <p>
                                    @if($provision->priority >= 8)
                                        <span class="badge badge-danger">{{ $provision->priority }}</span>
                                    @else
                                        <span class="badge badge-info">{{ $provision->priority }}</span>
                                    @endif
                                </p>

                                <strong>Ngày tạo:</strong>
                                <p>{{ $provision->created_at->format('d/m/Y H:i') }}</p>

                                @if($provision->provisioned_at)
                                <strong>Hoàn thành lúc:</strong>
                                <p>{{ $provision->provisioned_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                        </div>

                        @if($provision->provision_notes)
                        <div class="mt-3">
                            <strong>Ghi chú:</strong>
                            <div class="bg-light p-3 rounded">
                                {{ $provision->provision_notes }}
                            </div>
                        </div>
                        @endif

                        @if($provision->failure_reason)
                        <div class="mt-3">
                            <strong>Lý do thất bại:</strong>
                            <div class="alert alert-danger">
                                {{ $provision->failure_reason }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Provision Data -->
                @if($provision->provision_data)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Dữ liệu Provision</h3>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded">{{ json_encode($provision->provision_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                @endif

                <!-- Logs -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Lịch sử hoạt động</h3>
                    </div>
                    <div class="card-body">
                        @if($provision->logs->count() > 0)
                        <div class="timeline">
                            @foreach($provision->logs as $log)
                            <div class="time-label">
                                <span class="bg-{{ $log->getSeverityColor() }}">{{ $log->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div>
                                <i class="fas fa-circle bg-{{ $log->getSeverityColor() }}"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> {{ $log->created_at->format('H:i') }}
                                    </span>
                                    <h3 class="timeline-header">
                                        {{ $log->getActionLabel() }}
                                        @if($log->performedBy)
                                            bởi <strong>{{ $log->performedBy->name }}</strong>
                                        @endif
                                    </h3>
                                    @if($log->additional_data || $log->error_message)
                                    <div class="timeline-body">
                                        @if($log->error_message)
                                            <div class="alert alert-danger">{{ $log->error_message }}</div>
                                        @endif
                                        @if($log->additional_data)
                                            <pre class="small">{{ json_encode($log->additional_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted">Chưa có log nào.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Thao tác nhanh</h3>
                    </div>
                    <div class="card-body">
                        @if($provision->isPending())
                            <button type="button" class="btn btn-success btn-block mb-2" onclick="startProcessing({{ $provision->id }})">
                                <i class="fas fa-play"></i> Bắt đầu xử lý
                            </button>
                        @endif

                        @if($provision->isProcessing())
                            <button type="button" class="btn btn-success btn-block mb-2" onclick="completeProvision({{ $provision->id }})">
                                <i class="fas fa-check"></i> Hoàn thành
                            </button>
                        @endif

                        @if($provision->isPending() || $provision->isProcessing())
                            <button type="button" class="btn btn-danger btn-block mb-2" onclick="failProvision({{ $provision->id }})">
                                <i class="fas fa-times"></i> Đánh dấu thất bại
                            </button>
                        @endif

                        @if($provision->isFailed())
                            <button type="button" class="btn btn-warning btn-block mb-2" onclick="retryProvision({{ $provision->id }})">
                                <i class="fas fa-redo"></i> Thử lại
                            </button>
                        @endif

                        @if(!$provision->isCompleted() && !$provision->isCancelled())
                            <button type="button" class="btn btn-secondary btn-block mb-2" onclick="cancelProvision({{ $provision->id }})">
                                <i class="fas fa-ban"></i> Hủy
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Thống kê</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-6">Lượt xem:</dt>
                            <dd class="col-sm-6">{{ $provision->view_count }}</dd>
                            
                            @if($provision->last_viewed_at)
                            <dt class="col-sm-6">Xem lần cuối:</dt>
                            <dd class="col-sm-6">{{ $provision->last_viewed_at->format('d/m/Y H:i') }}</dd>
                            @endif
                            
                            @if($provision->estimated_completion)
                            <dt class="col-sm-6">Dự kiến hoàn thành:</dt>
                            <dd class="col-sm-6">{{ $provision->estimated_completion->format('d/m/Y H:i') }}</dd>
                            @endif
                            
                            <dt class="col-sm-6">Số logs:</dt>
                            <dd class="col-sm-6">{{ $provision->logs->count() }}</dd>
                        </dl>
                    </div>
                </div>

                <!-- Template Info -->
                @if($provision->template)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Template</h3>
                    </div>
                    <div class="card-body">
                        <strong>{{ $provision->template->name }}</strong>
                        @if($provision->template->description)
                            <p class="text-muted small mt-2">{{ $provision->template->description }}</p>
                        @endif
                        <p class="small">
                            <span class="badge badge-info">v{{ $provision->template->version }}</span>
                            @if($provision->template->estimated_duration)
                                <br><small class="text-muted">Thời gian ước tính: {{ $provision->template->estimated_duration }} phút</small>
                            @endif
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('js')
<script>
    function startProcessing(provisionId) {
        if (!confirm('Bắt đầu xử lý provision này?')) return;
        
        fetch(`/admin/provisions/${provisionId}/start-processing`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function completeProvision(provisionId) {
        if (!confirm('Đánh dấu provision này là hoàn thành?')) return;
        
        fetch(`/admin/provisions/${provisionId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                delivery_method: 'manual'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function failProvision(provisionId) {
        const reason = prompt('Nhập lý do thất bại:');
        if (!reason) return;

        fetch(`/admin/provisions/${provisionId}/fail`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                failure_reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function retryProvision(provisionId) {
        if (!confirm('Thử lại provision này?')) return;

        fetch(`/admin/provisions/${provisionId}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function cancelProvision(provisionId) {
        const reason = prompt('Nhập lý do hủy:');
        if (!reason) return;

        fetch(`/admin/provisions/${provisionId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                cancel_reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
</script>
@endpush