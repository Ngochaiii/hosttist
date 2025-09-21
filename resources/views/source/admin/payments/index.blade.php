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
                                                        <div class="modal fade" id="approveModal{{ $payment->id }}"
                                                            tabindex="-1" role="dialog"
                                                            aria-labelledby="approveModalLabel{{ $payment->id }}"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog" role="document">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title"
                                                                            id="approveModalLabel{{ $payment->id }}">Xác
                                                                            nhận thanh toán</h5>
                                                                        <button type="button" class="close"
                                                                            data-dismiss="modal" aria-label="Close">
                                                                            <span aria-hidden="true">&times;</span>
                                                                        </button>
                                                                    </div>
                                                                    <form
                                                                        action="{{ route('admin.payments.approve', $payment->id) }}"
                                                                        method="POST">
                                                                        @csrf
                                                                        <div class="modal-body">
                                                                            <p>Bạn có chắc chắn muốn xác nhận thanh toán
                                                                                này?</p>
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

                                                                            <p class="text-success">Đơn hàng sẽ được chuyển
                                                                                sang trạng thái đang xử lý.</p>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary"
                                                                                data-dismiss="modal">Hủy</button>
                                                                            <button type="submit"
                                                                                class="btn btn-success">Xác nhận</button>
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
