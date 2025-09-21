@extends('layouts.admin.index')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Quản lý thanh toán</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Thanh toán</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách thanh toán</h3>

                            <div class="card-tools">
                                <div class="btn-group">
                                    <a href="{{ route('admin.payments.index', ['status' => 'all']) }}"
                                        class="btn btn-sm {{ $status == 'all' ? 'btn-primary' : 'btn-default' }}">
                                        Tất cả <span class="badge badge-light">{{ $counts['all'] }}</span>
                                    </a>
                                    <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}"
                                        class="btn btn-sm {{ $status == 'pending' ? 'btn-primary' : 'btn-default' }}">
                                        Chờ xử lý <span class="badge badge-warning">{{ $counts['pending'] }}</span>
                                    </a>
                                    <a href="{{ route('admin.payments.index', ['status' => 'completed']) }}"
                                        class="btn btn-sm {{ $status == 'completed' ? 'btn-primary' : 'btn-default' }}">
                                        Đã xác nhận <span class="badge badge-success">{{ $counts['completed'] }}</span>
                                    </a>
                                    <a href="{{ route('admin.payments.index', ['status' => 'failed']) }}"
                                        class="btn btn-sm {{ $status == 'failed' ? 'btn-primary' : 'btn-default' }}">
                                        Đã từ chối <span class="badge badge-danger">{{ $counts['failed'] }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mã giao dịch</th>
                                            <th>Mã hóa đơn</th>
                                            <th>Khách hàng</th>
                                            <th>Dịch vụ</th>
                                            <th>Domain</th>
                                            <th>Số tiền</th>
                                            <th>Phương thức</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày yêu cầu</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($payments as $payment)
                                            @php
                                                // Lấy domain từ order_items
                                                $domainItems = [];
                                                if ($payment->invoice && $payment->invoice->order) {
                                                    $orderItems = \App\Models\Order_items::where(
                                                        'order_id',
                                                        $payment->invoice->order->id,
                                                    )
                                                        ->whereNotNull('domain')
                                                        ->get();

                                                    foreach ($orderItems as $item) {
                                                        if (!empty($item->domain)) {
                                                            $domainItems[] = $item->domain;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $payment->transaction_id }}</td>
                                                <td>{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                                <td>
                                                    @if ($payment->order && $payment->order->customer && $payment->order->customer->user)
                                                        {{ $payment->order->customer->user->name }}<br>
                                                        <small>{{ $payment->order->customer->user->email }}</small>
                                                    @else
                                                        <span class="text-muted">Không có thông tin</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($payment->invoice && $payment->invoice->order)
                                                        @php
                                                            $orderItems = \App\Models\Order_items::where(
                                                                'order_id',
                                                                $payment->invoice->order->id,
                                                            )->get();
                                                        @endphp

                                                        @foreach ($orderItems as $item)
                                                            <div class="mb-2">
                                                                <strong>{{ $item->name }}</strong>
                                                                @php
                                                                    $options = json_decode($item->options, true) ?: [];
                                                                @endphp

                                                                @if (!empty($options))
                                                                    <button type="button" class="btn btn-xs btn-info ml-2"
                                                                        data-toggle="modal"
                                                                        data-target="#optionsModal{{ $item->id }}">
                                                                        <i class="fas fa-info-circle"></i> Chi tiết
                                                                    </button>

                                                                    <!-- Modal chi tiết -->
                                                                    <div class="modal fade"
                                                                        id="optionsModal{{ $item->id }}">
                                                                        <div class="modal-dialog">
                                                                            <div class="modal-content">
                                                                                <div class="modal-header">
                                                                                    <h5 class="modal-title">Thông tin dịch
                                                                                        vụ: {{ $item->name }}</h5>
                                                                                    <button type="button" class="close"
                                                                                        data-dismiss="modal">
                                                                                        <span>&times;</span>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="modal-body">
                                                                                    <table class="table table-sm">
                                                                                        @foreach ($options as $key => $value)
                                                                                            @if (!in_array($key, ['service_type']))
                                                                                                <tr>
                                                                                                    <th width="40%">
                                                                                                        {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                                                                    </th>
                                                                                                    <td>
                                                                                                        @if (is_bool($value))
                                                                                                            {{ $value ? 'Có' : 'Không' }}
                                                                                                        @else
                                                                                                            {{ $value }}
                                                                                                        @endif
                                                                                                    </td>
                                                                                                </tr>
                                                                                            @endif
                                                                                        @endforeach
                                                                                    </table>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td>
                                                    @foreach ($domainItems as $domain)
                                                        <span class="badge badge-info">{{ $domain }}</span><br>
                                                    @endforeach

                                                    @if (count($domainItems) == 0)
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>{{ number_format($payment->amount, 0, ',', '.') }} đ</td>
                                                <td>
                                                    @if ($payment->payment_method == 'bank')
                                                        <span class="badge badge-info">Chuyển khoản ngân hàng</span>
                                                    @elseif($payment->payment_method == 'momo')
                                                        <span class="badge badge-primary">Ví MoMo</span>
                                                    @elseif($payment->payment_method == 'zalopay')
                                                        <span class="badge badge-primary">ZaloPay</span>
                                                    @elseif($payment->payment_method == 'wallet')
                                                        <span class="badge badge-success">Ví điện tử</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($payment->status == 'pending')
                                                        <span class="badge badge-warning">Chờ xử lý</span>
                                                    @elseif($payment->status == 'completed')
                                                        <span class="badge badge-success">Đã xác nhận</span>
                                                    @elseif($payment->status == 'failed')
                                                        <span class="badge badge-danger">Đã từ chối</span>
                                                    @endif
                                                </td>
                                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    @if ($payment->status == 'pending')
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            data-toggle="modal"
                                                            data-target="#approveModal{{ $payment->id }}">
                                                            <i class="fas fa-check"></i> Xác nhận
                                                        </button>

                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            data-toggle="modal"
                                                            data-target="#rejectModal{{ $payment->id }}">
                                                            <i class="fas fa-times"></i> Từ chối
                                                        </button>
                                                        <!-- Modal Xác nhận -->
                                                        <!-- Modal Xác nhận - Sửa lại logic check theo Category meta_data -->
                                                        <div class="modal fade" id="approveModal{{ $payment->id }}"
                                                            tabindex="-1" role="dialog"
                                                            aria-labelledby="approveModalLabel{{ $payment->id }}"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog modal-xl" role="document">
                                                                <div class="modal-content">
                                                                    <form
                                                                        action="{{ route('admin.payments.approve', $payment->id) }}"
                                                                        method="POST" enctype="multipart/form-data"
                                                                        id="approveForm{{ $payment->id }}">
                                                                        @csrf

                                                                        <div class="modal-header bg-success">
                                                                            <h5 class="modal-title text-white">
                                                                                <i class="fas fa-check-circle"></i> Xác nhận
                                                                                thanh toán và nhập thông tin dịch vụ
                                                                            </h5>
                                                                            <button type="button" class="close text-white"
                                                                                data-dismiss="modal">
                                                                                <span>&times;</span>
                                                                            </button>
                                                                        </div>

                                                                        <div class="modal-body">
                                                                            <!-- Thông tin thanh toán -->
                                                                            <div class="alert alert-info">
                                                                                <div class="row">
                                                                                    <div class="col-md-6">
                                                                                        <strong>Mã GD:</strong>
                                                                                        {{ $payment->transaction_id }}<br>
                                                                                        <strong>Khách hàng:</strong>
                                                                                        {{ $payment->order->customer->user->name ?? 'N/A' }}
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <strong>Số tiền:</strong>
                                                                                        {{ number_format($payment->amount, 0, ',', '.') }}
                                                                                        đ<br>
                                                                                        <strong>Email:</strong>
                                                                                        {{ $payment->order->customer->user->email ?? 'N/A' }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            @php
                                                                                $needsProvision = false;
                                                                                $provisionItems = [];

                                                                                if (
                                                                                    $payment->invoice &&
                                                                                    $payment->invoice->order
                                                                                ) {
                                                                                    $orderItems = \App\Models\Order_items::where(
                                                                                        'order_id',
                                                                                        $payment->invoice->order->id,
                                                                                    )
                                                                                        ->with(['product.category'])
                                                                                        ->get();

                                                                                    foreach ($orderItems as $item) {
                                                                                        if (
                                                                                            $item->product &&
                                                                                            $item->product->category
                                                                                        ) {
                                                                                            // Check service type từ category meta_data
                                                                                            $serviceType = $item->product->category->getServiceType();

                                                                                            // Chỉ cần provision cho các loại này
                                                                                            if (
                                                                                                in_array($serviceType, [
                                                                                                    'vps',
                                                                                                    'hosting',
                                                                                                    'ssl',
                                                                                                    'domain',
                                                                                                ])
                                                                                            ) {
                                                                                                $needsProvision = true;
                                                                                                $provisionItems[] = [
                                                                                                    'item' => $item,
                                                                                                    'service_type' => $serviceType,
                                                                                                    'service_label' => $item->product->category->getServiceLabel(),
                                                                                                ];
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            @endphp

                                                                            @if ($needsProvision && count($provisionItems) > 0)
                                                                                <!-- Tabs cho từng dịch vụ cần provision -->
                                                                                <ul class="nav nav-tabs"
                                                                                    id="serviceTab{{ $payment->id }}"
                                                                                    role="tablist">
                                                                                    @foreach ($provisionItems as $index => $provItem)
                                                                                        <li class="nav-item">
                                                                                            <a class="nav-link {{ $index == 0 ? 'active' : '' }}"
                                                                                                id="tab-{{ $payment->id }}-{{ $provItem['item']->id }}"
                                                                                                data-toggle="tab"
                                                                                                href="#service-{{ $payment->id }}-{{ $provItem['item']->id }}">
                                                                                                @if ($provItem['service_type'] == 'vps')
                                                                                                    <i
                                                                                                        class="fas fa-server"></i>
                                                                                                @elseif($provItem['service_type'] == 'hosting')
                                                                                                    <i
                                                                                                        class="fas fa-globe"></i>
                                                                                                @elseif($provItem['service_type'] == 'ssl')
                                                                                                    <i
                                                                                                        class="fas fa-lock"></i>
                                                                                                @elseif($provItem['service_type'] == 'domain')
                                                                                                    <i
                                                                                                        class="fas fa-link"></i>
                                                                                                @endif
                                                                                                {{ $provItem['service_label'] }}
                                                                                                <br><small>{{ Str::limit($provItem['item']->name, 20) }}</small>
                                                                                            </a>
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>

                                                                                <!-- Tab Content -->
                                                                                <div class="tab-content mt-3">
                                                                                    @foreach ($provisionItems as $index => $provItem)
                                                                                        @php
                                                                                            $item = $provItem['item'];
                                                                                            $serviceType =
                                                                                                $provItem[
                                                                                                    'service_type'
                                                                                                ];
                                                                                            $options =
                                                                                                json_decode(
                                                                                                    $item->options,
                                                                                                    true,
                                                                                                ) ?:
                                                                                                [];
                                                                                        @endphp

                                                                                        <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}"
                                                                                            id="service-{{ $payment->id }}-{{ $item->id }}">

                                                                                            <h6
                                                                                                class="border-bottom pb-2 mb-3">
                                                                                                <i class="fas fa-cog"></i>
                                                                                                {{ $item->name }}
                                                                                                @if (isset($options['domain']) || $item->domain)
                                                                                                    - <span
                                                                                                        class="badge badge-info">{{ $options['domain'] ?? $item->domain }}</span>
                                                                                                @endif
                                                                                            </h6>

                                                                                            @if ($serviceType == 'vps')
                                                                                                <!-- VPS Fields -->
                                                                                                <div class="row">
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>IP Server
                                                                                                                <span
                                                                                                                    class="text-danger">*</span></label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][server_ip]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="192.168.1.100"
                                                                                                                required>
                                                                                                            <input
                                                                                                                type="hidden"
                                                                                                                name="provision[{{ $item->id }}][service_type]"
                                                                                                                value="vps">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Port
                                                                                                                SSH</label>
                                                                                                            <input
                                                                                                                type="number"
                                                                                                                name="provision[{{ $item->id }}][port]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                value="22">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="row">
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Username
                                                                                                                <span
                                                                                                                    class="text-danger">*</span></label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][username]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="root"
                                                                                                                required>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Password
                                                                                                                <span
                                                                                                                    class="text-danger">*</span></label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][password]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="Nhập password VPS"
                                                                                                                required>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="form-group">
                                                                                                    <label>Hệ điều
                                                                                                        hành</label>
                                                                                                    <select
                                                                                                        name="provision[{{ $item->id }}][os]"
                                                                                                        class="form-control form-control-sm">
                                                                                                        @if (isset($options['os']))
                                                                                                            <option
                                                                                                                value="{{ $options['os'] }}"
                                                                                                                selected>
                                                                                                                {{ $options['os'] }}
                                                                                                            </option>
                                                                                                        @endif
                                                                                                        <option
                                                                                                            value="Ubuntu 20.04">
                                                                                                            Ubuntu 20.04 LTS
                                                                                                        </option>
                                                                                                        <option
                                                                                                            value="Ubuntu 22.04">
                                                                                                            Ubuntu 22.04 LTS
                                                                                                        </option>
                                                                                                        <option
                                                                                                            value="CentOS 7">
                                                                                                            CentOS 7
                                                                                                        </option>
                                                                                                        <option
                                                                                                            value="AlmaLinux 8">
                                                                                                            AlmaLinux 8
                                                                                                        </option>
                                                                                                        <option
                                                                                                            value="Debian 11">
                                                                                                            Debian 11
                                                                                                        </option>
                                                                                                        <option
                                                                                                            value="Windows Server 2019">
                                                                                                            Windows Server
                                                                                                            2019</option>
                                                                                                        <option
                                                                                                            value="Windows Server 2022">
                                                                                                            Windows Server
                                                                                                            2022</option>
                                                                                                    </select>
                                                                                                </div>
                                                                                                <div class="form-group">
                                                                                                    <label>Control Panel
                                                                                                        (nếu có)
                                                                                                    </label>
                                                                                                    <input type="text"
                                                                                                        name="provision[{{ $item->id }}][control_panel_url]"
                                                                                                        class="form-control form-control-sm"
                                                                                                        placeholder="https://vps.example.com:8443">
                                                                                                </div>
                                                                                            @elseif($serviceType == 'hosting')
                                                                                                <!-- Hosting Fields -->
                                                                                                <div class="row">
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>cPanel
                                                                                                                Username
                                                                                                                <span
                                                                                                                    class="text-danger">*</span></label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][cpanel_username]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                required>
                                                                                                            <input
                                                                                                                type="hidden"
                                                                                                                name="provision[{{ $item->id }}][service_type]"
                                                                                                                value="hosting">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>cPanel
                                                                                                                Password
                                                                                                                <span
                                                                                                                    class="text-danger">*</span></label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][cpanel_password]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                required>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="row">
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>cPanel
                                                                                                                URL</label>
                                                                                                            <input
                                                                                                                type="url"
                                                                                                                name="provision[{{ $item->id }}][cpanel_url]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="https://cpanel.example.com:2083">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Server/Hosting
                                                                                                                Name</label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][server_name]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="server1.example.com">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="form-group">
                                                                                                    <label>Nameservers</label>
                                                                                                    <textarea name="provision[{{ $item->id }}][nameservers]" class="form-control form-control-sm" rows="2"
                                                                                                        placeholder="ns1.example.com&#10;ns2.example.com"></textarea>
                                                                                                </div>
                                                                                                <div class="row">
                                                                                                    <div class="col-md-4">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>FTP
                                                                                                                Host</label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][ftp_host]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="ftp.example.com">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-4">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>FTP
                                                                                                                Username</label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][ftp_username]"
                                                                                                                class="form-control form-control-sm">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-4">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>FTP
                                                                                                                Password</label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][ftp_password]"
                                                                                                                class="form-control form-control-sm">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @elseif($serviceType == 'ssl')
                                                                                                <!-- SSL Fields -->
                                                                                                <div
                                                                                                    class="alert alert-warning">
                                                                                                    <i
                                                                                                        class="fas fa-info-circle"></i>
                                                                                                    Upload các file SSL cho
                                                                                                    domain:
                                                                                                    <strong>{{ $options['domain'] ?? ($item->domain ?? 'N/A') }}</strong>
                                                                                                </div>
                                                                                                <input type="hidden"
                                                                                                    name="provision[{{ $item->id }}][service_type]"
                                                                                                    value="ssl">
                                                                                                <input type="hidden"
                                                                                                    name="provision[{{ $item->id }}][domain]"
                                                                                                    value="{{ $options['domain'] ?? ($item->domain ?? '') }}">

                                                                                                <div class="form-group">
                                                                                                    <label>Certificate File
                                                                                                        (.crt, .pem) <span
                                                                                                            class="text-danger">*</span></label>
                                                                                                    <div
                                                                                                        class="custom-file">
                                                                                                        <input
                                                                                                            type="file"
                                                                                                            name="provision_files[{{ $item->id }}][certificate]"
                                                                                                            class="custom-file-input"
                                                                                                            accept=".crt,.pem,.cer"
                                                                                                            required>
                                                                                                        <label
                                                                                                            class="custom-file-label">Chọn
                                                                                                            file
                                                                                                            certificate</label>
                                                                                                    </div>
                                                                                                </div>

                                                                                                <div class="form-group">
                                                                                                    <label>Private Key File
                                                                                                        (.key, .pem)</label>
                                                                                                    <div
                                                                                                        class="custom-file">
                                                                                                        <input
                                                                                                            type="file"
                                                                                                            name="provision_files[{{ $item->id }}][private_key]"
                                                                                                            class="custom-file-input"
                                                                                                            accept=".key,.pem">
                                                                                                        <label
                                                                                                            class="custom-file-label">Chọn
                                                                                                            file private
                                                                                                            key</label>
                                                                                                    </div>
                                                                                                </div>

                                                                                                <div class="form-group">
                                                                                                    <label>CA Bundle
                                                                                                        File</label>
                                                                                                    <div
                                                                                                        class="custom-file">
                                                                                                        <input
                                                                                                            type="file"
                                                                                                            name="provision_files[{{ $item->id }}][ca_bundle]"
                                                                                                            class="custom-file-input"
                                                                                                            accept=".crt,.pem,.ca-bundle">
                                                                                                        <label
                                                                                                            class="custom-file-label">Chọn
                                                                                                            file CA
                                                                                                            bundle</label>
                                                                                                    </div>
                                                                                                </div>

                                                                                                <div class="row">
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Ngày hết
                                                                                                                hạn
                                                                                                                SSL</label>
                                                                                                            <input
                                                                                                                type="date"
                                                                                                                name="provision[{{ $item->id }}][expiry_date]"
                                                                                                                class="form-control form-control-sm">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>SSL
                                                                                                                Provider</label>
                                                                                                            <select
                                                                                                                name="provision[{{ $item->id }}][ssl_provider]"
                                                                                                                class="form-control form-control-sm">
                                                                                                                <option
                                                                                                                    value="">
                                                                                                                    -- Chọn
                                                                                                                    nhà cung
                                                                                                                    cấp --
                                                                                                                </option>
                                                                                                                <option
                                                                                                                    value="letsencrypt">
                                                                                                                    Let's
                                                                                                                    Encrypt
                                                                                                                </option>
                                                                                                                <option
                                                                                                                    value="comodo">
                                                                                                                    Comodo/Sectigo
                                                                                                                </option>
                                                                                                                <option
                                                                                                                    value="digicert">
                                                                                                                    DigiCert
                                                                                                                </option>
                                                                                                                <option
                                                                                                                    value="globalsign">
                                                                                                                    GlobalSign
                                                                                                                </option>
                                                                                                                <option
                                                                                                                    value="godaddy">
                                                                                                                    GoDaddy
                                                                                                                </option>
                                                                                                            </select>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @elseif($serviceType == 'domain')
                                                                                                <!-- Domain Fields -->
                                                                                                <div class="row">
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Domain</label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                class="form-control form-control-sm"
                                                                                                                value="{{ $options['domain'] ?? ($item->domain ?? '') }}"
                                                                                                                readonly>
                                                                                                            <input
                                                                                                                type="hidden"
                                                                                                                name="provision[{{ $item->id }}][domain]"
                                                                                                                value="{{ $options['domain'] ?? ($item->domain ?? '') }}">
                                                                                                            <input
                                                                                                                type="hidden"
                                                                                                                name="provision[{{ $item->id }}][service_type]"
                                                                                                                value="domain">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Registrar
                                                                                                                (Nhà đăng
                                                                                                                ký)</label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][registrar]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="GoDaddy, Namecheap, P.A Việt Nam...">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="form-group">
                                                                                                    <label>Nameservers</label>
                                                                                                    <textarea name="provision[{{ $item->id }}][nameservers]" class="form-control form-control-sm" rows="2"
                                                                                                        placeholder="ns1.example.com&#10;ns2.example.com"></textarea>
                                                                                                </div>
                                                                                                <div class="row">
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Auth/EPP
                                                                                                                Code</label>
                                                                                                            <input
                                                                                                                type="text"
                                                                                                                name="provision[{{ $item->id }}][auth_code]"
                                                                                                                class="form-control form-control-sm"
                                                                                                                placeholder="Transfer authorization code">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="col-md-6">
                                                                                                        <div
                                                                                                            class="form-group">
                                                                                                            <label>Ngày hết
                                                                                                                hạn</label>
                                                                                                            <input
                                                                                                                type="date"
                                                                                                                name="provision[{{ $item->id }}][expiry_date]"
                                                                                                                class="form-control form-control-sm">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="form-group">
                                                                                                    <label>Domain Control
                                                                                                        Panel URL</label>
                                                                                                    <input type="url"
                                                                                                        name="provision[{{ $item->id }}][control_panel_url]"
                                                                                                        class="form-control form-control-sm"
                                                                                                        placeholder="https://manage.example.com">
                                                                                                </div>
                                                                                            @endif

                                                                                            <!-- Ghi chú cho mỗi dịch vụ -->
                                                                                            <div class="form-group">
                                                                                                <label>Ghi chú / Thông tin
                                                                                                    thêm</label>
                                                                                                <textarea name="provision[{{ $item->id }}][notes]" class="form-control form-control-sm" rows="2"
                                                                                                    placeholder="Thông tin bổ sung cho khách hàng..."></textarea>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                <!-- Không cần provision -->
                                                                                <div class="alert alert-success">
                                                                                    <i class="fas fa-check-circle"></i>
                                                                                    Đơn hàng này không có dịch vụ cần nhập
                                                                                    thông tin thủ công.
                                                                                    Sẽ được kích hoạt tự động sau khi xác
                                                                                    nhận.
                                                                                </div>
                                                                            @endif
                                                                        </div>

                                                                        <div class="modal-footer">
                                                                            <button type="button"
                                                                                class="btn btn-secondary"
                                                                                data-dismiss="modal">
                                                                                <i class="fas fa-times"></i> Hủy
                                                                            </button>
                                                                            <button type="submit"
                                                                                class="btn btn-success">
                                                                                <i class="fas fa-check"></i> Xác nhận thanh
                                                                                toán
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>




                                                        <!-- Modal Từ chối -->
                                                        <div class="modal fade" id="rejectModal{{ $payment->id }}"
                                                            tabindex="-1" role="dialog"
                                                            aria-labelledby="rejectModalLabel{{ $payment->id }}"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title"
                                                                            id="rejectModalLabel{{ $payment->id }}">Từ
                                                                            chối thanh toán</h5>
                                                                        <button type="button" class="close"
                                                                            data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <form
                                                                        action="{{ route('admin.payments.reject', $payment->id) }}"
                                                                        method="POST">
                                                                        @csrf
                                                                        <div class="modal-body">
                                                                            <p>Bạn có chắc chắn muốn từ chối thanh toán này?
                                                                            </p>
                                                                            <p><strong>Mã giao dịch:</strong>
                                                                                {{ $payment->transaction_id }}</p>
                                                                            <p><strong>Mã hóa đơn:</strong>
                                                                                {{ $payment->invoice->invoice_number ?? 'N/A' }}
                                                                            </p>
                                                                            <p><strong>Khách hàng:</strong>
                                                                                {{ $payment->order->customer->user->name ?? 'Không có thông tin' }}
                                                                            </p>
                                                                            <p><strong>Số tiền:</strong>
                                                                                {{ number_format($payment->amount, 0, ',', '.') }}
                                                                                đ</p>

                                                                            <!-- Hiển thị domain cho admin -->
                                                                            @if (count($domainItems) > 0)
                                                                                <p><strong>Domain:</strong></p>
                                                                                <ul>
                                                                                    @foreach ($domainItems as $domain)
                                                                                        <li>{{ $domain }}</li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            @endif

                                                                            <div class="form-group">
                                                                                <label for="reason">Lý do từ chối <span
                                                                                        class="text-danger">*</span></label>
                                                                                <input type="text" class="form-control"
                                                                                    id="reason" name="reason"
                                                                                    required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button"
                                                                                class="btn btn-secondary"
                                                                                data-dismiss="modal">Hủy</button>
                                                                            <button type="submit"
                                                                                class="btn btn-danger">Từ chối</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center">Không có thanh toán nào</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                {{ $payments->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('js')
    <script>
        // File input label update
        $(document).on('change', '.custom-file-input', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').html(fileName || 'Chọn file');
        });
    </script>
@endpush

n
