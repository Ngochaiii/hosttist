{{-- resources/views/customer/services/index.blade.php --}}
@extends('layouts.web.default')

@section('content')
    <section class="services_section layout_padding">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>Dịch vụ của tôi</h2>
                <p>Quản lý và theo dõi tất cả dịch vụ đã mua</p>
            </div>

            <!-- Alert Messages -->
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

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills justify-content-center mb-4">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#provisions-tab">
                        <i class="fa fa-cog"></i>
                        Dịch vụ đang triển khai
                        @if ($provisionStats['total'] > 0)
                            <span class="badge badge-primary ml-1">{{ $provisionStats['total'] }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#services-tab">
                        <i class="fa fa-server"></i>
                        Dịch vụ đang hoạt động
                        @if ($serviceStats['total'] > 0)
                            <span class="badge badge-success ml-1">{{ $serviceStats['total'] }}</span>
                        @endif
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Service Provisions Tab -->
                <div class="tab-pane fade show active" id="provisions-tab">
                    <!-- Provision Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $provisionStats['total'] }}</h4>
                                    <small>Tổng số</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $provisionStats['pending'] + $provisionStats['processing'] }}</h4>
                                    <small>Đang xử lý</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $provisionStats['completed'] }}</h4>
                                    <small>Hoàn thành</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $provisionStats['failed'] }}</h4>
                                    <small>Thất bại</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row">
                                <input type="hidden" name="tab" value="provisions">
                                <div class="col-md-3">
                                    <select name="status" class="form-control">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang
                                            chờ</option>
                                        <option value="processing"
                                            {{ request('status') == 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                            Hoàn thành</option>
                                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất
                                            bại</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="type" class="form-control">
                                        <option value="">Tất cả loại</option>
                                        <option value="hosting" {{ request('type') == 'hosting' ? 'selected' : '' }}>
                                            Hosting</option>
                                        <option value="domain" {{ request('type') == 'domain' ? 'selected' : '' }}>Domain
                                        </option>
                                        <option value="ssl" {{ request('type') == 'ssl' ? 'selected' : '' }}>SSL
                                            Certificate</option>
                                        <option value="service" {{ request('type') == 'service' ? 'selected' : '' }}>Dịch
                                            vụ khác</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Tìm kiếm tên dịch vụ..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-block">Lọc</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Service Provisions List -->
                    @if ($provisions->count() > 0)
                        <div class="row">
                            @foreach ($provisions as $provision)
                                <div class="col-lg-6 col-md-12 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title m-0">
                                                {{ $provision->product->name ?? 'N/A' }}
                                            </h5>
                                            <span
                                                class="badge badge-{{ App\Helpers\ServiceHelper::getStatusColor($provision->provision_status) }}">
                                                {{ $provision->getStatusLabel() }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="text-muted">Loại dịch vụ:</small>
                                                    <p class="mb-2">{{ ucfirst($provision->product->type ?? 'N/A') }}</p>

                                                    <small class="text-muted">Ngày tạo:</small>
                                                    <p class="mb-2">{{ $provision->created_at->format('d/m/Y') }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    @if ($provision->provisioned_at)
                                                        <small class="text-muted">Ngày kích hoạt:</small>
                                                        <p class="mb-2">{{ $provision->provisioned_at->format('d/m/Y') }}
                                                        </p>
                                                    @endif

                                                    @if ($provision->estimated_completion)
                                                        <small class="text-muted">Dự kiến hoàn thành:</small>
                                                        <p class="mb-2">
                                                            {{ $provision->estimated_completion->format('d/m/Y') }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($provision->provision_notes)
                                                <div class="mt-3">
                                                    <small class="text-muted">Ghi chú:</small>
                                                    <p class="small">{{ Str::limit($provision->provision_notes, 100) }}
                                                    </p>
                                                </div>
                                            @endif

                                            <!-- Progress Bar for Processing Services -->
                                            @if (in_array($provision->provision_status, ['pending', 'processing']))
                                                <div class="mt-3">
                                                    <div class="progress">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning"
                                                            style="width: {{ $provision->provision_status == 'pending' ? '25%' : '75%' }}">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ $provision->provision_status == 'pending' ? 'Đang chờ xử lý...' : 'Đang triển khai...' }}
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group btn-block">
                                                <a href="{{ route('customer.services.provision.show', $provision->id) }}"
                                                    class="btn btn-primary">
                                                    <i class="fa fa-eye"></i> Chi tiết
                                                </a>
                                                @if ($provision->isCompleted())
                                                    <a href="{{ route('customer.services.provision.credentials', $provision->id) }}"
                                                        class="btn btn-success">
                                                        <i class="fa fa-key"></i> Thông tin truy cập
                                                    </a>
                                                @endif
                                                @if ($provision->isCompleted() && $provision->product->type === 'ssl')
                                                    <a href="{{ route('customer.services.ssl.download', [$provision->id, 'all']) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fa fa-download"></i> Download SSL
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Provisions Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $provisions->appends(array_merge(request()->query(), ['tab' => 'provisions']))->links() }}
                        </div>
                    @else
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fa fa-cog fa-4x text-muted mb-3"></i>
                                <h4>Chưa có dịch vụ nào đang triển khai</h4>
                                <p class="text-muted">Không có dịch vụ nào đang trong quá trình cung cấp</p>
                                <a href="{{ route('homepage') }}" class="btn btn-primary mt-3">
                                    Đặt mua dịch vụ
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Customer Services Tab -->
                <div class="tab-pane fade" id="services-tab">
                    <!-- Service Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $serviceStats['total'] }}</h4>
                                    <small>Tổng dịch vụ</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $serviceStats['active'] }}</h4>
                                    <small>Đang hoạt động</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $serviceStats['expired'] }}</h4>
                                    <small>Hết hạn</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $serviceStats['suspended'] }}</h4>
                                    <small>Tạm ngưng</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Services List -->
                    @if ($customerServices->count() > 0)
                        <div class="row">
                            @foreach ($customerServices as $service)
                                <div class="col-lg-6 col-md-12 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title m-0">
                                                {{ $service->name }}
                                            </h5>
                                            <span
                                                class="badge badge-{{ App\Helpers\ServiceHelper::getServiceStatusColor($service->service_status) }}">
                                                {{ ucfirst($service->service_status) }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="text-muted">Loại:</small>
                                                    <p class="mb-2">{{ ucfirst($service->type) }}</p>

                                                    <small class="text-muted">Bắt đầu:</small>
                                                    <p class="mb-2">
                                                        {{ $service->start_date ? Carbon\Carbon::parse($service->start_date)->format('d/m/Y') : 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <small class="text-muted">Kết thúc:</small>
                                                    <p class="mb-2">
                                                        {{ $service->end_date ? Carbon\Carbon::parse($service->end_date)->format('d/m/Y') : 'N/A' }}
                                                    </p>

                                                    @if ($service->next_due_date)
                                                        <small class="text-muted">Gia hạn tiếp theo:</small>
                                                        <p class="mb-2">
                                                            {{ Carbon\Carbon::parse($service->next_due_date)->format('d/m/Y') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($service->auto_renew)
                                                <div class="mt-2">
                                                    <span class="badge badge-info">
                                                        <i class="fa fa-refresh"></i> Tự động gia hạn
                                                    </span>
                                                </div>
                                            @endif

                                            <!-- Service expiry warning -->
                                            @if ($service->end_date && Carbon\Carbon::parse($service->end_date)->diffInDays(now()) <= 30)
                                                <div class="alert alert-warning mt-3 py-2">
                                                    <small>
                                                        <i class="fa fa-warning"></i>
                                                        Dịch vụ sẽ hết hạn trong
                                                        {{ Carbon\Carbon::parse($service->end_date)->diffInDays(now()) }}
                                                        ngày
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="btn-group btn-block">
                                                <a href="{{ route('customer.services.service.show', $service->id) }}"
                                                    class="btn btn-primary">
                                                    <i class="fa fa-eye"></i> Chi tiết
                                                </a>
                                                @if (in_array($service->service_status, ['active', 'suspended']))
                                                    <a href="{{ route('customer.services.service.credentials', $service->id) }}"
                                                        class="btn btn-success">
                                                        <i class="fa fa-info-circle"></i> Thông tin dịch vụ
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Services Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $customerServices->appends(array_merge(request()->query(), ['tab' => 'services']))->links() }}
                        </div>
                    @else
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fa fa-server fa-4x text-muted mb-3"></i>
                                <h4>Chưa có dịch vụ nào hoạt động</h4>
                                <p class="text-muted">Bạn chưa có dịch vụ nào đang hoạt động</p>
                                <a href="{{ route('homepage') }}" class="btn btn-primary mt-3">
                                    Khám phá dịch vụ
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <style>
            .card {
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border: none;
                transition: transform 0.2s;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }

            /* Provision status badges */
            .badge-pending {
                background-color: #6c757d;
            }

            .badge-processing {
                background-color: #ffc107;
            }

            .badge-completed {
                background-color: #28a745;
            }

            .badge-failed {
                background-color: #dc3545;
            }

            .badge-cancelled {
                background-color: #6c757d;
            }

            /* Service status badges */
            .badge-active {
                background-color: #28a745;
            }

            .badge-expired {
                background-color: #ffc107;
            }

            .badge-suspended {
                background-color: #fd7e14;
            }

            .nav-pills .nav-link.active {
                background-color: #007bff;
            }

            .nav-pills .nav-link {
                border-radius: 25px;
                margin: 0 5px;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Handle tab switching based on URL parameter
                const urlParams = new URLSearchParams(window.location.search);
                const activeTab = urlParams.get('tab');

                if (activeTab === 'services') {
                    $('.nav-pills .nav-link').removeClass('active');
                    $('.nav-pills .nav-link[href="#services-tab"]').addClass('active');
                    $('.tab-pane').removeClass('show active');
                    $('#services-tab').addClass('show active');
                }

                // Auto refresh cho các service đang processing
                @if ($provisions->where('provision_status', 'processing')->count() > 0)
                    setTimeout(function() {
                        location.reload();
                    }, 30000); // Refresh sau 30 giây nếu có service đang xử lý
                @endif
            });
        </script>
    @endpush

    @php

        function getServiceStatusColor($status)
        {
            return match ($status) {
                'active' => 'success',
                'expired' => 'warning',
                'suspended' => 'warning',
                'cancelled' => 'danger',
                default => 'secondary',
            };
        }
    @endphp
@endsection
