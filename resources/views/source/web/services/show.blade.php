{{-- resources/views/customer/services/show-service.blade.php --}}
@extends('layouts.web.default')

@section('content')
<section class="service_detail_section layout_padding">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.services.index') }}">Dịch vụ của tôi</a></li>
                        <li class="breadcrumb-item active">{{ $service->name }}</li>
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
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Service Info Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="m-0">
                            <i class="fa fa-server"></i>
                            {{ $service->name }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Mã dịch vụ:</strong></td>
                                        <td>{{ $service->sku ?? '#'.$service->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Loại dịch vụ:</strong></td>
                                        <td>{{ ucfirst($service->type) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái:</strong></td>
                                        <td>
                                            <span class="badge badge-{{ App\Helpers\ServiceHelper::getStatusColor($service->service_status) }} badge-lg">
                                                {{ ucfirst($service->service_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ngày bắt đầu:</strong></td>
                                        <td>{{ $service->start_date ? Carbon\Carbon::parse($service->start_date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @if($service->end_date)
                                    <tr>
                                        <td><strong>Ngày kết thúc:</strong></td>
                                        <td>{{ Carbon\Carbon::parse($service->end_date)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    @if($service->next_due_date)
                                    <tr>
                                        <td><strong>Gia hạn tiếp theo:</strong></td>
                                        <td>{{ Carbon\Carbon::parse($service->next_due_date)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Tự động gia hạn:</strong></td>
                                        <td>
                                            @if($service->auto_renew)
                                                <span class="badge badge-success">Có</span>
                                            @else
                                                <span class="badge badge-secondary">Không</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Định kỳ:</strong></td>
                                        <td>
                                            @if($service->is_recurring)
                                                <span class="badge badge-info">Có ({{ $service->recurring_period ?? 12 }} tháng)</span>
                                            @else
                                                <span class="badge badge-secondary">Không</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Giá hiện tại:</strong></td>
                                        <td>{{ number_format($service->price ?? 0, 0, ',', '.') }} đ</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($service->description)
                        <div class="mt-3">
                            <h6><strong>Mô tả dịch vụ:</strong></h6>
                            <div class="alert alert-info">
                                {!! $service->description !!}
                            </div>
                        </div>
                        @endif

                        <!-- Expiry warning -->
                        @if($service->end_date && Carbon\Carbon::parse($service->end_date)->diffInDays(now()) <= 30)
                        <div class="mt-4">
                            <div class="alert alert-warning">
                                <i class="fa fa-warning"></i>
                                <strong>Thông báo:</strong> Dịch vụ sẽ hết hạn trong {{ Carbon\Carbon::parse($service->end_date)->diffInDays(now()) }} ngày.
                                @if($service->is_recurring)
                                    Vui lòng gia hạn để tránh gián đoạn dịch vụ.
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Parent Product Information -->
                @if($service->parentProduct)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">
                            <i class="fa fa-info-circle"></i>
                            Thông tin sản phẩm gốc
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Tên sản phẩm:</strong> {{ $service->parentProduct->name }}</p>
                                <p><strong>Mã sản phẩm:</strong> {{ $service->parentProduct->sku }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Loại:</strong> {{ ucfirst($service->parentProduct->type) }}</p>
                                <p><strong>Danh mục:</strong> {{ $service->parentProduct->category->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        
                        @if($service->parentProduct->short_description)
                        <p><strong>Mô tả ngắn:</strong> {{ $service->parentProduct->short_description }}</p>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Order Information -->
                @if($service->orderItems && $service->orderItems->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">
                            <i class="fa fa-shopping-cart"></i>
                            Thông tin đơn hàng
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($service->orderItems as $orderItem)
                        <div class="border-bottom pb-3 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Mã đơn hàng:</strong> 
                                        @if($orderItem->order)
                                            <a href="{{ route('customer.order.detail', $orderItem->order->id) }}">
                                                {{ $orderItem->order->order_number }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                    <p><strong>Số lượng:</strong> {{ $orderItem->quantity ?? 1 }}</p>
                                    @if($orderItem->domain)
                                    <p><strong>Domain:</strong> {{ $orderItem->domain }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Giá đặt hàng:</strong> {{ number_format($orderItem->total ?? 0, 0, ',', '.') }} đ</p>
                                    @if($orderItem->duration)
                                    <p><strong>Thời hạn đặt:</strong> {{ $orderItem->duration }} năm</p>
                                    @endif
                                    @if($orderItem->order)
                                    <p><strong>Ngày đặt:</strong> {{ $orderItem->order->created_at->format('d/m/Y H:i') }}</p>
                                    @endif
                                </div>
                            </div>

                            @if($orderItem->options)
                            @php
                                $options = json_decode($orderItem->options, true) ?? [];
                            @endphp
                            @if(!empty($options))
                            <div class="mt-3">
                                <h6><strong>Tùy chọn khi đặt hàng:</strong></h6>
                                <ul class="list-unstyled">
                                    @foreach($options as $key => $value)
                                    <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Service Metadata -->
                @if($service->meta_data)
                @php
                    $metaData = is_string($service->meta_data) 
                        ? json_decode($service->meta_data, true) 
                        : $service->meta_data;
                @endphp
                @if($metaData && is_array($metaData) && isset($metaData['service_info']))
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0">
                            <i class="fa fa-cogs"></i>
                            Thông tin kỹ thuật
                        </h5>
                    </div>
                    <div class="card-body">
                        @php $serviceInfo = $metaData['service_info']; @endphp
                        <div class="row">
                            <div class="col-md-6">
                                @if(isset($serviceInfo['created_from_order']))
                                <p><strong>Được tạo từ đơn hàng:</strong> #{{ $serviceInfo['created_from_order'] }}</p>
                                @endif
                                @if(isset($serviceInfo['provisioned_at']))
                                <p><strong>Ngày cung cấp:</strong> {{ Carbon\Carbon::parse($serviceInfo['provisioned_at'])->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @if(isset($serviceInfo['duration_years']))
                                <p><strong>Thời hạn ban đầu:</strong> {{ $serviceInfo['duration_years'] }} năm</p>
                                @endif
                                @if(isset($serviceInfo['original_product_id']))
                                <p><strong>ID sản phẩm gốc:</strong> #{{ $serviceInfo['original_product_id'] }}</p>
                                @endif
                            </div>
                        </div>

                        @if(isset($metaData['domain']))
                        <p><strong>Domain:</strong> {{ $metaData['domain'] }}</p>
                        @endif
                    </div>
                </div>
                @endif
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">Thao tác nhanh</h5>
                    </div>
                    <div class="card-body">
                        @if(in_array($service->service_status, ['active', 'suspended']))
                            <a href="{{ route('customer.services.service.credentials', $service->id) }}" 
                               class="btn btn-success btn-block mb-2">
                                <i class="fa fa-info-circle"></i> Xem thông tin dịch vụ
                            </a>
                        @endif

                        @if($service->orderItems->count() > 0 && $service->orderItems->first()->order)
                        <a href="{{ route('customer.order.detail', $service->orderItems->first()->order->id) }}" 
                           class="btn btn-outline-primary btn-block mb-2">
                            <i class="fa fa-shopping-cart"></i> Xem đơn hàng
                        </a>
                        @endif

                        <a href="{{ route('customer.services.index') }}?tab=services" 
                           class="btn btn-outline-secondary btn-block">
                            <i class="fa fa-arrow-left"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>

                <!-- Service Management -->
                @if($service->service_status === 'active')
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="m-0">Quản lý dịch vụ</h5>
                    </div>
                    <div class="card-body">
                        @if($service->is_recurring)
                        <form action="{{ route('customer.services.service.renew', $service->id) }}" method="POST" class="mb-3">
                            @csrf
                            <div class="form-group">
                                <label class="small">Gia hạn thêm:</label>
                                <select name="years" class="form-control form-control-sm">
                                    <option value="1">1 năm</option>
                                    <option value="2">2 năm</option>
                                    <option value="3">3 năm</option>
                                    <option value="5">5 năm</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success btn-block btn-sm" 
                                    onclick="return confirm('Bạn có chắc muốn gia hạn dịch vụ này?')">
                                <i class="fa fa-refresh"></i> Gia hạn dịch vụ
                            </button>
                        </form>
                        @endif

                        <button type="button" class="btn btn-danger btn-block btn-sm" 
                                data-toggle="modal" data-target="#cancelModal">
                            <i class="fa fa-times"></i> Yêu cầu hủy dịch vụ
                        </button>
                    </div>
                </div>
                @endif

                <!-- Service Status -->
                <div class="card mb-4">
                    <div class="card-header bg-{{ App\Helpers\ServiceHelper::getStatusColor($service->service_status) }} text-white">
                        <h5 class="m-0">
                            <i class="fa fa-info-circle"></i>
                            Trạng thái
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <span class="badge badge-{{ App\Helpers\ServiceHelper::getStatusColor($service->service_status) }} badge-lg mb-3">
                                {{ ucfirst($service->service_status) }}
                            </span>
                            
                            @if($service->end_date)
                                @php
                                    $daysLeft = Carbon\Carbon::parse($service->end_date)->diffInDays(now());
                                    $isExpiringSoon = $daysLeft <= 30;
                                @endphp
                                <div class="mt-3">
                                    <small class="text-muted">Thời gian còn lại:</small><br>
                                    <strong class="text-{{ $isExpiringSoon ? 'warning' : 'info' }}">
                                        {{ $daysLeft }} ngày
                                    </strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Service Specs -->
                @if($service->parentProduct && $service->parentProduct->meta_data)
                @php
                    $specs = is_string($service->parentProduct->meta_data) 
                        ? json_decode($service->parentProduct->meta_data, true) 
                        : $service->parentProduct->meta_data;
                @endphp
                @if($specs && is_array($specs))
                <div class="card">
                    <div class="card-header">
                        <h5 class="m-0">Thông số kỹ thuật</h5>
                    </div>
                    <div class="card-body">
                        @foreach($specs as $key => $value)
                            @if(is_scalar($value) && !in_array($key, ['service_info']))
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                <strong class="small">{{ $value }}</strong>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
                @endif
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
                        <i class="fa fa-warning"></i>
                        <strong>Cảnh báo:</strong> Việc hủy dịch vụ có thể dẫn đến mất dữ liệu và không thể khôi phục. 
                        Dịch vụ sẽ được chuyển sang trạng thái "cancelled" và không thể sử dụng.
                    </div>
                    
                    <div class="mb-3">
                        <strong>Thông tin dịch vụ sẽ hủy:</strong><br>
                        <span class="text-muted">{{ $service->name }}</span><br>
                        <span class="text-muted">{{ $service->type }} - {{ $service->sku ?? '#'.$service->id }}</span>
                    </div>

                    <div class="form-group">
                        <label>Lý do hủy dịch vụ: <span class="text-danger">*</span></label>
                        <select name="reason" class="form-control" required>
                            <option value="">Chọn lý do...</option>
                            <option value="Không sử dụng nữa">Không sử dụng nữa</option>
                            <option value="Chi phí quá cao">Chi phí quá cao</option>
                            <option value="Chuyển sang nhà cung cấp khác">Chuyển sang nhà cung cấp khác</option>
                            <option value="Không hài lòng với dịch vụ">Không hài lòng với dịch vụ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú thêm:</label>
                        <textarea name="additional_notes" class="form-control" rows="3" 
                                  placeholder="Mô tả chi tiết lý do hủy dịch vụ (tùy chọn)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-check"></i> Xác nhận hủy dịch vụ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
.badge-lg {
    font-size: 0.95em;
    padding: 0.5em 0.75em;
}

.badge-active { background-color: #28a745; }
.badge-expired { background-color: #ffc107; }
.badge-suspended { background-color: #fd7e14; }
.badge-cancelled { background-color: #dc3545; }

.border-bottom:last-child {
    border-bottom: none !important;
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Auto refresh if service is in transitional state
    @if(in_array($service->service_status, ['pending', 'processing']))
        setTimeout(function() {
            location.reload();
        }, 60000); // Refresh after 1 minute
    @endif

    // Form validation for cancel modal
    $('#cancelModal form').on('submit', function(e) {
        const reason = $(this).find('select[name="reason"]').val();
        if (!reason) {
            e.preventDefault();
            alert('Vui lòng chọn lý do hủy dịch vụ.');
            return false;
        }

        return confirm('Bạn có chắc chắn muốn hủy dịch vụ này? Hành động này không thể hoàn tác.');
    });
});
</script>
@endpush

@php
function getServiceStatusColor($status) {
    return match($status) {
        'active' => 'success',
        'expired' => 'warning',
        'suspended' => 'warning',
        'cancelled' => 'danger',
        default => 'secondary'
    };
}
@endphp
@endsection