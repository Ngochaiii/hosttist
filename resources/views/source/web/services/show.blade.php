{{-- resources/views/customer/services/show-service.blade.php --}}
@extends('layouts.web.default')

@section('content')
    <style>
        .svc-tech { --tech-accent: #6366f1; --tech-accent-2: #22d3ee; }
        .svc-tech .tech-card { border: 1px solid rgba(99, 102, 241, .12); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(15, 23, 42, .06); }
        .svc-tech .tech-hero {
            background: linear-gradient(120deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
            color: #fff; padding: 24px 24px 20px; position: relative; overflow: hidden;
        }
        .svc-tech .tech-hero::after {
            content: ""; position: absolute; inset: 0;
            background: radial-gradient(circle at 85% -10%, rgba(34, 211, 238, .35), transparent 55%);
            pointer-events: none;
        }
        .svc-tech .order-code {
            font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace;
            letter-spacing: .5px; background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .18); padding: 3px 10px; border-radius: 999px;
        }
        .svc-tech .info-tile {
            background: #fff; border: 1px solid rgba(15, 23, 42, .06); border-radius: 12px;
            padding: 14px 16px; display: flex; align-items: center; gap: 12px; height: 100%;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .svc-tech .info-tile:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(99, 102, 241, .12); }
        .svc-tech .info-tile .icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--tech-accent), var(--tech-accent-2));
            color: #fff; font-size: 1rem; flex-shrink: 0;
        }
        .svc-tech .tile-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; margin-bottom: 2px; }
        .svc-tech .tile-value { font-weight: 600; color: #0f172a; word-break: break-word; }
        .svc-tech .price-cell { font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace; }
        .svc-tech .badge-tech { border-radius: 999px; font-weight: 500; }
        .svc-tech .status-pulse { animation: svc-pulse 1.8s ease-in-out infinite; }
        @keyframes svc-pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 211, 238, .45); }
            70% { box-shadow: 0 0 0 8px rgba(34, 211, 238, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 211, 238, 0); }
        }
        .svc-tech .terminal-card { background: #0b1120; border-radius: 14px; overflow: hidden; border: 1px solid rgba(34, 211, 238, .25); }
        .svc-tech .terminal-header {
            background: #111827; padding: 10px 16px; display: flex; align-items: center; gap: 8px;
            border-bottom: 1px solid rgba(34, 211, 238, .15);
        }
        .svc-tech .terminal-header .dot-btn { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .svc-tech .terminal-title { color: #cbd5e1; font-size: .8rem; margin-left: 6px; font-family: 'SFMono-Regular', Consolas, monospace; }
        .svc-tech .terminal-body { padding: 16px; }
        .svc-tech .terminal-card .tile-label { color: #64748b; }
        .svc-tech .terminal-card .tile-value { color: #5eead4; }
        .svc-tech .btn-copy-tech { background: linear-gradient(135deg, var(--tech-accent), var(--tech-accent-2)); border: none; color: #fff; }
        .svc-tech .btn-copy-tech:hover { color: #fff; opacity: .9; }
        .svc-tech .status-ring { padding: 20px; text-align: center; }
        .svc-tech .ssl-file-row {
            display: flex; align-items: center;
            border: 1px solid rgba(15, 23, 42, .06); border-radius: 12px;
            padding: 12px 14px; margin-bottom: 10px; background: #fff;
        }
        .svc-tech .ssl-file-row:last-child { margin-bottom: 0; }
        .svc-tech .ssl-note {
            background: #fff8e6; border: 1px solid #f5d78e; border-radius: 10px;
            padding: 10px 14px; color: #7a5c11;
        }
        .svc-tech .ssl-file-row .icon {
            width: 38px; height: 38px; border-radius: 10px; margin-right: 12px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            background: rgba(99, 102, 241, .1); color: var(--tech-accent);
        }
    </style>

    <section class="service_detail_section layout_padding svc-tech">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('customer.services.index') }}">Dịch vụ của tôi</a>
                            </li>
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
                    @php
                        $svcTypeIcon = match($service->type ?? null) {
                            'ssl' => 'fa-lock',
                            'domain' => 'fa-globe',
                            'hosting' => 'fa-server',
                            'vps' => 'fa-server',
                            default => 'fa-cube',
                        };
                        $svcStatusColor = App\Helpers\ServiceHelper::getStatusColor($service->service_status);
                    @endphp
                    <!-- Service Info Card -->
                    <div class="card tech-card mb-4">
                        <div class="tech-hero">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="text-uppercase small mb-1" style="letter-spacing:.08em; color:rgba(255,255,255,.6);">
                                        <i class="fa {{ $svcTypeIcon }}"></i> Chi tiết dịch vụ
                                    </div>
                                    <h4 class="mb-2">{{ $service->name }}</h4>
                                    <div class="small" style="color:rgba(255,255,255,.6);">
                                        <span class="order-code">{{ $service->sku ?? '#' . $service->id }}</span>
                                        <span class="ms-2"><i class="fa fa-tag"></i> {{ ucfirst($service->type) }}</span>
                                    </div>
                                </div>
                                <span class="badge bg-{{ $svcStatusColor }} badge-tech badge-lg {{ $service->service_status === 'active' ? 'status-pulse' : '' }}">
                                    {{ ucfirst($service->service_status) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-6 col-md-4">
                                    <div class="info-tile">
                                        <span class="icon"><i class="fa fa-hashtag"></i></span>
                                        <div>
                                            <div class="tile-label">Mã dịch vụ</div>
                                            <div class="tile-value">{{ $service->sku ?? '#' . $service->id }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="info-tile">
                                        <span class="icon"><i class="fa fa-calendar"></i></span>
                                        <div>
                                            <div class="tile-label">Ngày bắt đầu</div>
                                            <div class="tile-value">{{ $service->start_date ? Carbon\Carbon::parse($service->start_date)->format('d/m/Y H:i') : 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                                @if ($service->end_date)
                                    <div class="col-6 col-md-4">
                                        <div class="info-tile">
                                            <span class="icon"><i class="fa fa-calendar-times-o"></i></span>
                                            <div>
                                                <div class="tile-label">Ngày kết thúc</div>
                                                <div class="tile-value">{{ Carbon\Carbon::parse($service->end_date)->format('d/m/Y H:i') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($service->next_due_date)
                                    <div class="col-6 col-md-4">
                                        <div class="info-tile">
                                            <span class="icon"><i class="fa fa-refresh"></i></span>
                                            <div>
                                                <div class="tile-label">Gia hạn tiếp theo</div>
                                                <div class="tile-value">{{ Carbon\Carbon::parse($service->next_due_date)->format('d/m/Y H:i') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-6 col-md-4">
                                    <div class="info-tile">
                                        <span class="icon"><i class="fa fa-repeat"></i></span>
                                        <div>
                                            <div class="tile-label">Tự động gia hạn</div>
                                            <div class="tile-value">
                                                @if ($service->auto_renew)
                                                    <span class="badge badge-success badge-tech">Có</span>
                                                @else
                                                    <span class="badge badge-secondary badge-tech">Không</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="info-tile">
                                        <span class="icon"><i class="fa fa-clock-o"></i></span>
                                        <div>
                                            <div class="tile-label">Định kỳ</div>
                                            <div class="tile-value">
                                                @if ($service->is_recurring)
                                                    <span class="badge badge-info badge-tech">Có
                                                        ({{ $service->recurring_period ?? 12 }} tháng)</span>
                                                @else
                                                    <span class="badge badge-secondary badge-tech">Không</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="info-tile">
                                        <span class="icon"><i class="fa fa-money"></i></span>
                                        <div>
                                            <div class="tile-label">Giá hiện tại</div>
                                            <div class="tile-value price-cell">{{ number_format($service->price ?? 0, 0, ',', '.') }} đ</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($service->description)
                                <div class="mt-3">
                                    <h6><strong>Mô tả dịch vụ:</strong></h6>
                                    <div class="alert alert-info">
                                        {!! $service->description !!}
                                    </div>
                                </div>
                            @endif

                            <!-- Expiry warning -->
                            @if ($service->end_date && Carbon\Carbon::parse($service->end_date)->diffInDays(now()) <= 30)
                                <div class="mt-4">
                                    <div class="alert alert-warning">
                                        <i class="fa fa-warning"></i>
                                        <strong>Thông báo:</strong> Dịch vụ sẽ hết hạn trong
                                        {{ Carbon\Carbon::parse($service->end_date)->diffInDays(now()) }} ngày.
                                        @if ($service->is_recurring)
                                            Vui lòng gia hạn để tránh gián đoạn dịch vụ.
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- File chứng chỉ SSL -->
                    @if (!empty($sslDownloads ?? []))
                        <div class="card tech-card mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="m-0">
                                    <i class="fa fa-certificate" style="color:var(--tech-accent);"></i>
                                    File chứng chỉ SSL
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-3">
                                    @foreach ($sslDownloads as $file)
                                        <li class="ssl-file-row">
                                            <span class="icon"><i class="fa {{ $file['kind'] === 'private_key' ? 'fa-key' : 'fa-file-text-o' }}"></i></span>
                                            <div class="flex-grow-1">
                                                <div class="tile-value">{{ $file['label'] }}</div>
                                                <div class="small text-muted">{{ $file['desc'] }}</div>
                                            </div>
                                            <a href="{{ route('customer.services.ssl.download', [$service->id, $file['kind']]) }}"
                                                class="btn btn-sm btn-copy-tech">
                                                <i class="fa fa-download"></i> Tải về
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <a href="{{ route('customer.services.ssl.download', [$service->id, 'all']) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-file-archive-o"></i> Tải tất cả kèm hướng dẫn cài đặt (.tar.gz)
                                </a>

                                {{-- Không dùng class .alert: layout tự đóng mọi .alert sau 5 giây
                                     (dành cho flash message), ghi chú tĩnh sẽ biến mất theo. --}}
                                <div class="ssl-note mt-3 small">
                                    <i class="fa fa-shield"></i>
                                    <strong>Lưu ý bảo mật:</strong> Private Key chỉ dùng trên máy chủ của bạn,
                                    không gửi cho bất kỳ ai kể cả nhân viên hỗ trợ.
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Parent Product Information -->
                    @if ($service->parentProduct)
                        <div class="card tech-card mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="m-0">
                                    <i class="fa fa-info-circle" style="color:var(--tech-accent);"></i>
                                    Thông tin sản phẩm gốc
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-tile">
                                            <span class="icon"><i class="fa fa-cube"></i></span>
                                            <div>
                                                <div class="tile-label">Tên sản phẩm</div>
                                                <div class="tile-value">{{ $service->parentProduct->name }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-tile">
                                            <span class="icon"><i class="fa fa-hashtag"></i></span>
                                            <div>
                                                <div class="tile-label">Mã sản phẩm</div>
                                                <div class="tile-value">{{ $service->parentProduct->sku }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-tile">
                                            <span class="icon"><i class="fa fa-tag"></i></span>
                                            <div>
                                                <div class="tile-label">Loại</div>
                                                <div class="tile-value">{{ ucfirst($service->parentProduct->type) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-tile">
                                            <span class="icon"><i class="fa fa-folder"></i></span>
                                            <div>
                                                <div class="tile-label">Danh mục</div>
                                                <div class="tile-value">{{ $service->parentProduct->category->name ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if ($service->parentProduct->short_description)
                                    <p class="mt-3 mb-0"><strong>Mô tả ngắn:</strong> {{ $service->parentProduct->short_description }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Order Information -->
                    @if ($service->orderItems && $service->orderItems->count() > 0)
                        <div class="card tech-card mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="m-0">
                                    <i class="fa fa-shopping-cart" style="color:var(--tech-accent);"></i>
                                    Thông tin đơn hàng
                                </h5>
                            </div>
                            <div class="card-body">
                                @foreach ($service->orderItems as $orderItem)
                                    <div class="border rounded-3 p-3 mb-3" style="border-color: rgba(15,23,42,.08) !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                            <strong>
                                                @if ($orderItem->order)
                                                    <a href="{{ route('customer.order.detail', $orderItem->order->id) }}" class="order-code text-decoration-none">
                                                        #{{ $orderItem->order->order_number }}
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </strong>
                                            @if ($orderItem->order)
                                                <span class="small text-muted"><i class="fa fa-clock-o"></i> {{ $orderItem->order->created_at->format('d/m/Y H:i') }}</span>
                                            @endif
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-6 col-md-3">
                                                <div class="tile-label">Số lượng</div>
                                                <div class="tile-value">{{ $orderItem->quantity ?? 1 }}</div>
                                            </div>
                                            @if ($orderItem->domain)
                                                <div class="col-6 col-md-3">
                                                    <div class="tile-label">Domain</div>
                                                    <div class="tile-value">{{ $orderItem->domain }}</div>
                                                </div>
                                            @endif
                                            <div class="col-6 col-md-3">
                                                <div class="tile-label">Giá đặt hàng</div>
                                                <div class="tile-value price-cell">{{ number_format($orderItem->total ?? 0, 0, ',', '.') }} đ</div>
                                            </div>
                                            @php
                                                $orderItemDuration = \App\Helpers\ServiceHelper::orderItemDurationYears($orderItem);
                                            @endphp
                                            @if ($orderItemDuration)
                                                <div class="col-6 col-md-3">
                                                    <div class="tile-label">Thời hạn đặt</div>
                                                    <div class="tile-value"><span class="badge bg-light text-dark border badge-tech">{{ $orderItemDuration }} năm</span></div>
                                                </div>
                                            @endif
                                        </div>

                                        @if ($orderItem->options)
                                            @php
                                                $options = json_decode($orderItem->options, true) ?? [];
                                            @endphp
                                            @if (!empty($options))
                                                <div class="mt-3">
                                                    <div class="tile-label mb-1">Tùy chọn khi đặt hàng</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach ($options as $key => $value)
                                                            <span class="badge bg-light text-dark border badge-tech">
                                                                {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ is_array($value) ? implode(', ', $value) : $value }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Service Metadata -->
                    @if ($service->meta_data)
                        @php
                            $metaData = is_string($service->meta_data)
                                ? json_decode($service->meta_data, true)
                                : $service->meta_data;
                        @endphp
                        @if ($metaData && is_array($metaData) && isset($metaData['service_info']))
                            <div class="card tech-card">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="m-0">
                                        <i class="fa fa-cogs" style="color:var(--tech-accent);"></i>
                                        Thông tin kỹ thuật
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @php $serviceInfo = $metaData['service_info']; @endphp
                                    <div class="row g-3">
                                        @if (isset($serviceInfo['created_from_order']))
                                            <div class="col-md-6">
                                                <div class="tile-label">Được tạo từ đơn hàng</div>
                                                <div class="tile-value">#{{ $serviceInfo['created_from_order'] }}</div>
                                            </div>
                                        @endif
                                        @if (isset($serviceInfo['provisioned_at']))
                                            <div class="col-md-6">
                                                <div class="tile-label">Ngày cung cấp</div>
                                                <div class="tile-value">{{ Carbon\Carbon::parse($serviceInfo['provisioned_at'])->format('d/m/Y H:i') }}</div>
                                            </div>
                                        @endif
                                        @if (isset($serviceInfo['duration_years']))
                                            <div class="col-md-6">
                                                <div class="tile-label">Thời hạn ban đầu</div>
                                                <div class="tile-value">{{ $serviceInfo['duration_years'] }} năm</div>
                                            </div>
                                        @endif
                                        @if (isset($serviceInfo['original_product_id']))
                                            <div class="col-md-6">
                                                <div class="tile-label">ID sản phẩm gốc</div>
                                                <div class="tile-value">#{{ $serviceInfo['original_product_id'] }}</div>
                                            </div>
                                        @endif
                                    </div>

                                    @if (isset($metaData['domain']))
                                        <p class="mt-3 mb-0"><strong>Domain:</strong> {{ $metaData['domain'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card tech-card mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="m-0">Thao tác nhanh</h5>
                        </div>
                        <div class="card-body d-grid gap-2">
                            @if (in_array($service->service_status, ['active', 'suspended']))
                                {{-- Trang này dùng chung cho ServiceProvision và CustomerService; id không
                                     trùng nhau nên phải trỏ đúng route theo nguồn đang xem. --}}
                                <a href="{{ isset($provisionData)
                                    ? route('customer.services.provision.credentials', $service->id)
                                    : route('customer.services.service.credentials', $service->id) }}"
                                    class="btn btn-copy-tech btn-block">
                                    <i class="fa fa-info-circle"></i> Xem thông tin dịch vụ
                                </a>
                            @endif

                            @if ($service->orderItem && $service->orderItem->order)
                                <a href="{{ route('customer.order.detail', $service->orderItem->order->id) }}"
                                    class="btn btn-outline-primary btn-block">
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
                    @if ($service->service_status === 'active')
                        <div class="card tech-card mb-4">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="m-0">Quản lý dịch vụ</h5>
                            </div>
                            <div class="card-body d-grid gap-2">
                                @if ($service->is_recurring)
                                    <a href="{{ route('customer.services.service.renew.quote', $service->id) }}"
                                        class="btn btn-success btn-block btn-sm">
                                        <i class="fa fa-refresh"></i> Gia hạn dịch vụ
                                    </a>
                                @endif

                                <button type="button" class="btn btn-danger btn-block btn-sm" data-toggle="modal"
                                    data-target="#cancelModal">
                                    <i class="fa fa-times"></i> Yêu cầu hủy dịch vụ
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Service Status -->
                    <div class="card tech-card mb-4">
                        <div class="tech-hero status-ring">
                            <div class="text-uppercase small mb-2" style="letter-spacing:.08em; color:rgba(255,255,255,.6);">
                                <i class="fa fa-info-circle"></i> Trạng thái dịch vụ
                            </div>
                            <span class="badge bg-{{ App\Helpers\ServiceHelper::getStatusColor($service->service_status) }} badge-tech badge-lg {{ $service->service_status === 'active' ? 'status-pulse' : '' }}">
                                {{ ucfirst($service->service_status) }}
                            </span>

                            @if ($service->end_date)
                                @php
                                    $daysLeft = Carbon\Carbon::parse($service->end_date)->diffInDays(now());
                                    $isExpiringSoon = $daysLeft <= 30;
                                @endphp
                                <div class="mt-3">
                                    <small style="color:rgba(255,255,255,.6);">Thời gian còn lại</small><br>
                                    <strong style="font-size: 1.4rem; color: {{ $isExpiringSoon ? '#fbbf24' : '#22d3ee' }};">
                                        {{ $daysLeft }} ngày
                                    </strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Service Specs -->
                    @if ($service->parentProduct && $service->parentProduct->meta_data)
                        @php
                            $specs = is_string($service->parentProduct->meta_data)
                                ? json_decode($service->parentProduct->meta_data, true)
                                : $service->parentProduct->meta_data;
                        @endphp
                        @if ($specs && is_array($specs))
                            <div class="card tech-card">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="m-0"><i class="fa fa-microchip" style="color:var(--tech-accent);"></i> Thông số kỹ thuật</h5>
                                </div>
                                <div class="card-body">
                                    @foreach ($specs as $key => $value)
                                        @if (is_scalar($value) && !in_array($key, ['service_info']))
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px dashed rgba(15,23,42,.08);">
                                                <span class="small text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                                <strong class="small price-cell">{{ $value }}</strong>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            @if (isset($provisionData) && $service->provision_type === 'vps')
                <div class="terminal-card mb-4 mt-4">
                    <div class="terminal-header">
                        <span class="dot-btn" style="background:#ef4444;"></span>
                        <span class="dot-btn" style="background:#f59e0b;"></span>
                        <span class="dot-btn" style="background:#22c55e;"></span>
                        <span class="terminal-title"><i class="fa fa-server me-1"></i>vps-access.json</span>
                    </div>
                    <div class="terminal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="tile-label">Server IP</div>
                                <div class="tile-value">{{ $provisionData['server_ip'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="tile-label">Username</div>
                                <div class="tile-value">{{ $provisionData['username'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="tile-label">Port</div>
                                <div class="tile-value">{{ $provisionData['port'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="tile-label">OS</div>
                                <div class="tile-value">{{ $provisionData['os'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="tile-label">Status</div>
                                <div class="tile-value"><span class="badge bg-success badge-tech">{{ $provisionData['status'] ?? 'Active' }}</span></div>
                            </div>
                            <div class="col-md-6">
                                <div class="tile-label">Created</div>
                                <div class="tile-value">{{ $service->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('customer.services.provision.credentials', $service->id) }}"
                                class="btn btn-copy-tech">
                                <i class="fa fa-key"></i> Xem thông tin truy cập
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- Cancel Service Modal -->
    @if ($service->service_status === 'active')
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
                                <strong>Cảnh báo:</strong> Việc hủy dịch vụ có thể dẫn đến mất dữ liệu và không thể khôi
                                phục.
                                Dịch vụ sẽ được chuyển sang trạng thái "cancelled" và không thể sử dụng.
                            </div>

                            <div class="mb-3">
                                <strong>Thông tin dịch vụ sẽ hủy:</strong><br>
                                <span class="text-muted">{{ $service->name }}</span><br>
                                <span class="text-muted">{{ $service->type }} -
                                    {{ $service->sku ?? '#' . $service->id }}</span>
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

    @push('header_css')
        <style>
            .badge-lg {
                font-size: 0.95em;
                padding: 0.5em 0.75em;
            }

            .badge-active {
                background-color: #28a745;
            }

            .badge-expired {
                background-color: #ffc107;
            }

            .badge-suspended {
                background-color: #fd7e14;
            }

            .badge-cancelled {
                background-color: #dc3545;
            }

            .border-bottom:last-child {
                border-bottom: none !important;
                padding-bottom: 0 !important;
                margin-bottom: 0 !important;
            }
        </style>
    @endpush

    @push('footer_js')
        <script>
            $(document).ready(function() {
                // Auto refresh if service is in transitional state
                @if (in_array($service->service_status, ['pending', 'processing']))
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


@endsection
