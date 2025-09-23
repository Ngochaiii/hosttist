{{-- resources/views/customer/services/service-info.blade.php --}}
@extends('layouts.web.default')

@section('content')
<section class="service_info_section layout_padding">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.services.index') }}">Dịch vụ của tôi</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.services.service.show', $service->id) }}">{{ $service->name }}</a></li>
                        <li class="breadcrumb-item active">Thông tin dịch vụ</li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Security Warning -->
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-info-circle fa-2x mr-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1">Thông tin dịch vụ</h5>
                            <p class="mb-0">Dưới đây là thông tin chi tiết về dịch vụ đang hoạt động của bạn.</p>
                        </div>
                    </div>
                </div>

                <!-- Service Info -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="m-0">
                            <i class="fa fa-server"></i>
                            {{ $service->name }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Mã dịch vụ:</strong> {{ $service->sku ?? '#'.$service->id }}</p>
                                <p><strong>Loại dịch vụ:</strong> {{ ucfirst($service->type) }}</p>
                                <p><strong>Trạng thái:</strong> 
                                    <span class="badge badge-{{ App\Helpers\ServiceHelper::getStatusColor($service->service_status) }} badge-lg">
                                        {{ ucfirst($service->service_status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ngày bắt đầu:</strong> {{ $service->start_date ? Carbon\Carbon::parse($service->start_date)->format('d/m/Y') : 'N/A' }}</p>
                                <p><strong>Ngày kết thúc:</strong> {{ $service->end_date ? Carbon\Carbon::parse($service->end_date)->format('d/m/Y') : 'N/A' }}</p>
                                @if($service->next_due_date)
                                <p><strong>Gia hạn tiếp theo:</strong> {{ Carbon\Carbon::parse($service->next_due_date)->format('d/m/Y') }}</p>
                                @endif
                            </div>
                        </div>

                        @if($service->auto_renew)
                            <div class="alert alert-info">
                                <i class="fa fa-refresh"></i>
                                Dịch vụ này đã được thiết lập tự động gia hạn.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Service Details based on type -->
                @if($service->meta_data)
                    @php
                        $metaData = is_string($service->meta_data) 
                            ? json_decode($service->meta_data, true) 
                            : $service->meta_data;
                    @endphp

                    @if($metaData && is_array($metaData))
                        <!-- Hosting Service Details -->
                        @if($service->type === 'hosting' && isset($metaData['hosting_account']))
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0">
                                    <i class="fa fa-server"></i>
                                    Thông tin Hosting
                                </h5>
                            </div>
                            <div class="card-body">
                                @php $hosting = $metaData['hosting_account']; @endphp
                                <div class="row">
                                    <div class="col-md-6">
                                        @if(isset($hosting['control_panel_url']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Control Panel:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="{{ $hosting['control_panel_url'] }}" readonly>
                                                <div class="input-group-append">
                                                    <a href="{{ $hosting['control_panel_url'] }}" target="_blank" class="btn btn-outline-primary">
                                                        <i class="fa fa-external-link"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if(isset($hosting['username']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Username:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="{{ $hosting['username'] }}" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary copy-btn" data-clipboard-text="{{ $hosting['username'] }}">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if(isset($hosting['server_ip']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Server IP:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="{{ $hosting['server_ip'] }}" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary copy-btn" data-clipboard-text="{{ $hosting['server_ip'] }}">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        @if(isset($hosting['ftp_host']))
                                        <div class="mb-3">
                                            <label class="small text-muted">FTP Host:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="{{ $hosting['ftp_host'] }}" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary copy-btn" data-clipboard-text="{{ $hosting['ftp_host'] }}">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if(isset($hosting['disk_quota']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Disk Quota:</label>
                                            <input type="text" class="form-control" value="{{ $hosting['disk_quota'] }}" readonly>
                                        </div>
                                        @endif

                                        @if(isset($hosting['bandwidth_quota']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Bandwidth:</label>
                                            <input type="text" class="form-control" value="{{ $hosting['bandwidth_quota'] }}" readonly>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                @if(isset($hosting['nameservers']) && is_array($hosting['nameservers']))
                                <div class="mt-3">
                                    <h6>Nameservers:</h6>
                                    <div class="row">
                                        @foreach($hosting['nameservers'] as $index => $ns)
                                        <div class="col-md-6 mb-2">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NS{{ $index + 1 }}</span>
                                                </div>
                                                <input type="text" class="form-control" value="{{ $ns }}" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary copy-btn" data-clipboard-text="{{ $ns }}">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Domain Service Details -->
                        @if($service->type === 'domain' && isset($metaData['domain_registration']))
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0">
                                    <i class="fa fa-globe"></i>
                                    Thông tin Domain
                                </h5>
                            </div>
                            <div class="card-body">
                                @php $domain = $metaData['domain_registration']; @endphp
                                <div class="row">
                                    <div class="col-md-6">
                                        @if(isset($domain['domain']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Tên miền:</label>
                                            <input type="text" class="form-control" value="{{ $domain['domain'] }}" readonly>
                                        </div>
                                        @endif

                                        @if(isset($domain['registrar']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Registrar:</label>
                                            <input type="text" class="form-control" value="{{ $domain['registrar'] }}" readonly>
                                        </div>
                                        @endif

                                        @if(isset($domain['registration_date']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Ngày đăng ký:</label>
                                            <input type="text" class="form-control" value="{{ Carbon\Carbon::parse($domain['registration_date'])->format('d/m/Y') }}" readonly>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        @if(isset($domain['expiration_date']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Ngày hết hạn:</label>
                                            <input type="text" class="form-control" value="{{ Carbon\Carbon::parse($domain['expiration_date'])->format('d/m/Y') }}" readonly>
                                        </div>
                                        @endif

                                        @if(isset($domain['auto_renew']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Tự động gia hạn:</label>
                                            <input type="text" class="form-control" value="{{ $domain['auto_renew'] ? 'Có' : 'Không' }}" readonly>
                                        </div>
                                        @endif

                                        @if(isset($domain['auth_code']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Auth Code:</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control password-field" value="{{ $domain['auth_code'] }}" readonly id="auth-code">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary toggle-password" data-target="#auth-code">
                                                        <i class="fa fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-primary copy-btn" data-clipboard-text="{{ $domain['auth_code'] }}">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                @if(isset($domain['nameservers']) && is_array($domain['nameservers']))
                                <div class="mt-3">
                                    <h6>Nameservers:</h6>
                                    <div class="row">
                                        @foreach($domain['nameservers'] as $index => $ns)
                                        <div class="col-md-6 mb-2">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NS{{ $index + 1 }}</span>
                                                </div>
                                                <input type="text" class="form-control" value="{{ $ns }}" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary copy-btn" data-clipboard-text="{{ $ns }}">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- SSL Certificate Details -->
                        @if($service->type === 'ssl' && isset($metaData['ssl_certificate']))
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0">
                                    <i class="fa fa-lock"></i>
                                    Thông tin SSL Certificate
                                </h5>
                            </div>
                            <div class="card-body">
                                @php $ssl = $metaData['ssl_certificate']; @endphp
                                <div class="row">
                                    <div class="col-md-6">
                                        @if(isset($ssl['common_name']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Common Name:</label>
                                            <input type="text" class="form-control" value="{{ $ssl['common_name'] }}" readonly>
                                        </div>
                                        @endif

                                        @if(isset($ssl['status']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Trạng thái:</label>
                                            <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $ssl['status'])) }}" readonly>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        @if(isset($ssl['created_at']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Ngày tạo:</label>
                                            <input type="text" class="form-control" value="{{ Carbon\Carbon::parse($ssl['created_at'])->format('d/m/Y H:i') }}" readonly>
                                        </div>
                                        @endif

                                        @if(isset($ssl['expires_at']))
                                        <div class="mb-3">
                                            <label class="small text-muted">Ngày hết hạn:</label>
                                            <input type="text" class="form-control" value="{{ Carbon\Carbon::parse($ssl['expires_at'])->format('d/m/Y H:i') }}" readonly>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                @if(isset($ssl['subject_alternative_names']) && is_array($ssl['subject_alternative_names']))
                                <div class="mt-3">
                                    <h6>Subject Alternative Names (SAN):</h6>
                                    <div class="row">
                                        @foreach($ssl['subject_alternative_names'] as $san)
                                        <div class="col-md-6 mb-2">
                                            <input type="text" class="form-control form-control-sm" value="{{ $san }}" readonly>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endif
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">Thao tác</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('customer.services.service.show', $service->id) }}" 
                           class="btn btn-outline-primary btn-block mb-2">
                            <i class="fa fa-arrow-left"></i> Quay lại chi tiết
                        </a>
                        <a href="{{ route('customer.services.index') }}" 
                           class="btn btn-outline-secondary btn-block mb-3">
                            <i class="fa fa-list"></i> Danh sách dịch vụ
                        </a>

                        @if($service->service_status === 'active')
                        <!-- Service Management Actions -->
                        <hr>
                        <h6>Quản lý dịch vụ:</h6>
                        
                        @if($service->is_recurring)
                        <form action="{{ route('customer.services.service.renew', $service->id) }}" method="POST" class="mb-2">
                            @csrf
                            <div class="form-group">
                                <select name="years" class="form-control form-control-sm">
                                    <option value="1">1 năm</option>
                                    <option value="2">2 năm</option>
                                    <option value="3">3 năm</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success btn-block btn-sm" onclick="return confirm('Bạn có chắc muốn gia hạn dịch vụ này?')">
                                <i class="fa fa-refresh"></i> Gia hạn dịch vụ
                            </button>
                        </form>
                        @endif

                        <button type="button" class="btn btn-danger btn-block btn-sm" data-toggle="modal" data-target="#cancelModal">
                            <i class="fa fa-times"></i> Yêu cầu hủy dịch vụ
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Service Status -->
                <div class="card">
                    <div class="card-header bg-{{ App\Helpers\ServiceHelper::getStatusColor($service->service_status) }} text-white">
                        <h5 class="m-0">
                            <i class="fa fa-info-circle"></i>
                            Trạng thái dịch vụ
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Trạng thái hiện tại:</strong><br>
                        <span class="badge badge-{{ App\Helpers\ServiceHelper::getStatusColor($service->service_status) }} badge-lg">
                            {{ ucfirst($service->service_status) }}
                        </span></p>

                        @if($service->end_date)
                            @php
                                $daysLeft = Carbon\Carbon::parse($service->end_date)->diffInDays(now());
                                $isExpiringSoon = $daysLeft <= 30;
                            @endphp
                            <p><strong>Thời gian còn lại:</strong><br>
                            <span class="badge badge-{{ $isExpiringSoon ? 'warning' : 'info' }}">
                                {{ $daysLeft }} ngày
                            </span></p>
                            
                            @if($isExpiringSoon)
                            <div class="alert alert-warning py-2 mt-3">
                                <small>
                                    <i class="fa fa-warning"></i>
                                    Dịch vụ sẽ hết hạn sớm. Vui lòng gia hạn để tránh gián đoạn.
                                </small>
                            </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cancel Service Modal -->
@if($service->service_status === 'active')
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yêu cầu hủy dịch vụ</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('customer.services.service.cancel', $service->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Lưu ý:</strong> Việc hủy dịch vụ có thể dẫn đến mất dữ liệu và không thể khôi phục.
                    </div>
                    <div class="form-group">
                        <label>Lý do hủy dịch vụ:</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Vui lòng cho biết lý do hủy dịch vụ..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xác nhận hủy dịch vụ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('header_css')
<style>
.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.badge-active { background-color: #28a745; }
.badge-expired { background-color: #ffc107; }
.badge-suspended { background-color: #fd7e14; }

.copy-btn {
    white-space: nowrap;
}

.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: #28a745;
    color: white;
    padding: 12px 20px;
    border-radius: 5px;
    margin-bottom: 10px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.toast.show {
    opacity: 1;
    transform: translateX(0);
}
</style>
@endpush

@push('footer_js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize clipboard
    var clipboard = new ClipboardJS('.copy-btn');
    
    clipboard.on('success', function(e) {
        showToast('Đã sao chép vào clipboard!');
        e.clearSelection();
    });

    clipboard.on('error', function(e) {
        showToast('Lỗi khi sao chép. Vui lòng thử lại.', 'error');
    });

    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const target = $($(this).data('target'));
        const icon = $(this).find('i');
        
        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).html('<i class="fa fa-eye-slash"></i>');
        } else {
            target.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).html('<i class="fa fa-eye"></i>');
        }
    });

    // Show toast notification
    function showToast(message, type = 'success') {
        const toastContainer = $('.toast-container').length ? 
            $('.toast-container') : 
            $('<div class="toast-container"></div>').appendTo('body');
        
        const bgColor = type === 'success' ? '#28a745' : '#dc3545';
        const toast = $(`
            <div class="toast" style="background: ${bgColor}">
                <i class="fa fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                ${message}
            </div>
        `);
        
        toastContainer.append(toast);
        
        setTimeout(function() {
            toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            toast.removeClass('show');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    }
});
</script>
@endpush

@endsection