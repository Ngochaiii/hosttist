@extends('layouts.web.default')

@section('content')
<style>
    /* Lưu ý: layout web dùng Bootstrap 4.3 nên chỉ dùng class của BS4
       (mr-, ml-, float-, badge-). Các class BS5 như me-, ms-, g-, gap-,
       top-0, end-0, btn-close không tồn tại ở đây. */
    .order-tech { --tech-accent: #6366f1; --tech-accent-2: #22d3ee; }
    .order-tech .tech-card {
        border: 1px solid rgba(99, 102, 241, .12);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(15, 23, 42, .06);
    }
    .order-tech .tech-hero {
        background: linear-gradient(120deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
        color: #fff;
        padding: 26px 26px 22px;
        position: relative;
        overflow: hidden;
    }
    .order-tech .tech-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 85% -10%, rgba(34, 211, 238, .35), transparent 55%);
        pointer-events: none;
    }
    .order-tech .tech-hero > * { position: relative; z-index: 1; }
    .order-tech .tech-hero .order-code {
        font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace;
        letter-spacing: .5px;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .18);
        padding: 4px 12px;
        border-radius: 999px;
        display: inline-block;
        font-size: .85rem;
    }
    .order-tech .btn-back {
        background: rgba(255, 255, 255, .1);
        border: 1px solid rgba(255, 255, 255, .25);
        color: #fff;
        border-radius: 999px;
        white-space: nowrap;
    }
    .order-tech .btn-back:hover { background: rgba(255, 255, 255, .2); color: #fff; }

    /* Stepper — xuống hàng gọn trên mobile thay vì tràn ngang */
    .order-tech .tech-stepper { display: flex; align-items: center; margin-top: 22px; flex-wrap: wrap; }
    .order-tech .tech-step { display: flex; align-items: center; flex: 1 1 160px; min-width: 0; margin-bottom: 6px; }
    .order-tech .tech-step .dot {
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255, 255, 255, .1); border: 2px solid rgba(255, 255, 255, .25);
        font-size: .78rem; flex-shrink: 0; color: rgba(255,255,255,.7);
    }
    .order-tech .tech-step .label {
        margin-left: 10px; font-size: .8rem; color: rgba(255, 255, 255, .65);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .order-tech .tech-step .line { flex: 1 1 20px; height: 2px; background: rgba(255, 255, 255, .18); margin: 0 10px; min-width: 12px; }
    .order-tech .tech-step.done .dot { background: var(--tech-accent-2); border-color: var(--tech-accent-2); color: #0f172a; }
    .order-tech .tech-step.done .label { color: #fff; }
    .order-tech .tech-step.done .line { background: var(--tech-accent-2); }
    .order-tech .tech-step.active .dot {
        background: var(--tech-accent-2); border-color: var(--tech-accent-2); color: #0f172a;
        box-shadow: 0 0 0 4px rgba(34, 211, 238, .25);
        animation: tech-pulse 1.8s ease-in-out infinite;
    }
    .order-tech .tech-step.active .label { color: #fff; font-weight: 600; }
    .order-tech .tech-step.cancelled .dot { background: #ef4444; border-color: #ef4444; color: #fff; }
    @keyframes tech-pulse {
        0%, 100% { box-shadow: 0 0 0 4px rgba(34, 211, 238, .25); }
        50% { box-shadow: 0 0 0 8px rgba(34, 211, 238, .12); }
    }

    /* Info tiles */
    .order-tech .info-tile {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, .06);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        height: 100%;
        min-width: 0;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .order-tech .info-tile:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(99, 102, 241, .12); }
    .order-tech .info-tile .icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--tech-accent), var(--tech-accent-2));
        color: #fff; font-size: 1rem; flex-shrink: 0; margin-right: 12px;
    }
    .order-tech .info-tile .tile-body { min-width: 0; }
    .order-tech .info-tile .tile-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; margin-bottom: 2px; }
    .order-tech .info-tile .tile-value { font-weight: 600; color: #0f172a; word-break: break-word; }
    .order-tech .info-tile .tile-value.code {
        font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace;
        font-size: .82rem; margin-bottom: 4px;
    }

    /* Bảng sản phẩm */
    .order-tech .tech-table { margin-bottom: 0; }
    .order-tech .tech-table thead th {
        background: #0f172a; color: #e2e8f0; border: none;
        font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 600;
        padding: 12px 14px; white-space: nowrap;
    }
    .order-tech .tech-table tbody td { padding: 14px; vertical-align: middle; border-color: rgba(15, 23, 42, .06); }
    .order-tech .tech-table tbody tr:hover { background: rgba(99, 102, 241, .04); }
    .order-tech .product-avatar {
        width: 34px; height: 34px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99, 102, 241, .1); color: var(--tech-accent);
        margin-right: 8px; flex-shrink: 0;
    }
    .order-tech .badge-tech { border-radius: 999px; font-weight: 500; padding: 5px 10px; font-size: .74rem; }
    .order-tech .price-cell { font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace; font-weight: 600; color: #0f172a; white-space: nowrap; }

    /* Bảng tổng tiền */
    .order-tech .summary-box { max-width: 340px; margin-left: auto; }
    .order-tech .summary-box .line {
        display: flex; justify-content: space-between; align-items: center;
        padding: 7px 0; font-size: .9rem; color: #475569;
    }
    .order-tech .summary-box .line.total {
        border-top: 1px dashed rgba(15, 23, 42, .12); margin-top: 6px; padding-top: 12px;
        font-size: 1.05rem; font-weight: 700; color: #0f172a;
    }

    /* Dịch vụ đã bàn giao */
    .order-tech .service-row {
        display: flex; align-items: center; flex-wrap: wrap;
        border: 1px solid rgba(15, 23, 42, .06); border-radius: 12px;
        padding: 14px 16px; margin-bottom: 10px; background: #fff;
    }
    .order-tech .service-row:last-child { margin-bottom: 0; }
    .order-tech .service-row .icon {
        width: 38px; height: 38px; border-radius: 10px; margin-right: 12px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        background: rgba(99, 102, 241, .1); color: var(--tech-accent);
    }
    .order-tech .service-row .service-body { flex: 1 1 200px; min-width: 0; }
    .order-tech .service-row .service-name { font-weight: 600; color: #0f172a; word-break: break-word; }
    .order-tech .btn-tech {
        background: linear-gradient(135deg, var(--tech-accent), var(--tech-accent-2));
        border: none; color: #fff; white-space: nowrap;
    }
    .order-tech .btn-tech:hover { color: #fff; opacity: .9; }

    @media (max-width: 575.98px) {
        .order-tech .tech-hero { padding: 20px 18px; }
        .order-tech .service-row .btn { margin-top: 10px; width: 100%; }
    }
</style>

@php
    $statusMap = [
        'pending'    => ['Chờ thanh toán', 'warning'],
        'processing' => ['Đang xử lý', 'info'],
        'completed'  => ['Hoàn thành', 'success'],
        'cancelled'  => ['Đã hủy', 'danger'],
        'failed'     => ['Thất bại', 'danger'],
    ];
    [$orderStatusLabel, $orderStatusColor] = $statusMap[$order->status] ?? [ucfirst($order->status), 'secondary'];
@endphp

<div class="container py-5 order-tech">
    <div class="row">
        @include('source.web.profile.partials.sidebar')

        <div class="col-md-8">
            <!-- Header + tiến trình đơn hàng -->
            <div class="card tech-card mb-4">
                <div class="tech-hero">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="mr-3" style="min-width:0;">
                            <div class="text-uppercase small mb-1" style="letter-spacing:.08em; color:rgba(255,255,255,.6);">
                                <i class="fa fa-microchip"></i> Chi tiết đơn hàng
                            </div>
                            <h4 class="mb-2">Đơn hàng <span class="order-code">#{{ $order->order_number }}</span></h4>
                            <div class="small" style="color:rgba(255,255,255,.6);">
                                <i class="fa fa-clock-o"></i> {{ $order->created_at->format('d/m/Y H:i') }}
                                <span class="badge badge-{{ $orderStatusColor }} badge-tech ml-2">{{ $orderStatusLabel }}</span>
                            </div>
                        </div>
                        <a href="{{ route('customer.orders') }}" class="btn btn-sm btn-back">
                            <i class="fa fa-arrow-left"></i> Quay lại
                        </a>
                    </div>

                    @php
                        $statusSteps = ['pending' => 1, 'processing' => 2, 'completed' => 3];
                        $currentStep = $statusSteps[$order->status] ?? 0;
                    @endphp
                    @if ($currentStep > 0)
                        <div class="tech-stepper">
                            @foreach (['pending' => 'Chờ thanh toán', 'processing' => 'Đang xử lý', 'completed' => 'Hoàn thành'] as $key => $label)
                                @php $step = $statusSteps[$key]; @endphp
                                <div class="tech-step {{ $step < $currentStep ? 'done' : ($step === $currentStep ? 'active' : '') }}">
                                    <span class="dot">
                                        @if ($step < $currentStep)
                                            <i class="fa fa-check"></i>
                                        @else
                                            {{ $step }}
                                        @endif
                                    </span>
                                    <span class="label">{{ $label }}</span>
                                    @if (!$loop->last)
                                        <span class="line"></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="tech-stepper">
                            <div class="tech-step cancelled" style="flex:0 0 auto;">
                                <span class="dot"><i class="fa fa-times"></i></span>
                                <span class="label">{{ $orderStatusLabel }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card-body">
                    <!-- Thông tin nhanh -->
                    <div class="row">
                        <div class="col-12 col-md-4 mb-3">
                            <div class="info-tile">
                                <span class="icon"><i class="fa fa-calendar"></i></span>
                                <div class="tile-body">
                                    <div class="tile-label">Ngày đặt hàng</div>
                                    <div class="tile-value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <div class="info-tile">
                                <span class="icon"><i class="fa fa-money"></i></span>
                                <div class="tile-body">
                                    <div class="tile-label">Tổng tiền</div>
                                    <div class="tile-value">{{ number_format($order->total_amount, 0, ',', '.') }} đ</div>
                                </div>
                            </div>
                        </div>
                        @if ($order->invoice)
                            <div class="col-12 col-md-4 mb-3">
                                <div class="info-tile">
                                    <span class="icon"><i class="fa fa-file-text-o"></i></span>
                                    <div class="tile-body">
                                        <div class="tile-label">Hóa đơn</div>
                                        <div class="tile-value code">{{ $order->invoice->invoice_number }}</div>
                                        @if ($order->invoice->status == 'paid')
                                            <span class="badge badge-success badge-tech">Đã thanh toán</span>
                                        @elseif ($order->invoice->status == 'pending')
                                            <span class="badge badge-warning badge-tech">Chờ thanh toán</span>
                                        @else
                                            <span class="badge badge-secondary badge-tech">{{ ucfirst($order->invoice->status) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Sản phẩm đã mua -->
                    <h5 class="mt-3 mb-3"><i class="fa fa-shopping-cart mr-1" style="color:var(--tech-accent);"></i> Sản phẩm đã mua</h5>
                    <div class="table-responsive">
                        <table class="table tech-table align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Sản phẩm</th>
                                    <th>Loại</th>
                                    <th>Domain</th>
                                    <th>Thời hạn</th>
                                    <th class="text-right">Đơn giá</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $index => $item)
                                    @php
                                        $type = $item->product->type ?? null;
                                        $typeIcon = match ($type) {
                                            'ssl' => 'fa-lock',
                                            'domain' => 'fa-globe',
                                            'hosting' => 'fa-server',
                                            'vps' => 'fa-server',
                                            default => 'fa-cube',
                                        };
                                        $typeBadge = match ($type) {
                                            'ssl' => ['SSL', 'success'],
                                            'domain' => ['Domain', 'primary'],
                                            'hosting' => ['Hosting', 'info'],
                                            default => [ucfirst($type ?? 'N/A'), 'secondary'],
                                        };
                                        $domain = $item->domain ?: (json_decode($item->options, true)['domain'] ?? '');
                                        $period = \App\Helpers\ServiceHelper::orderItemDurationYears($item);
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span class="product-avatar"><i class="fa {{ $typeIcon }}"></i></span>{{ $item->name }}
                                        </td>
                                        <td><span class="badge badge-{{ $typeBadge[1] }} badge-tech">{{ $typeBadge[0] }}</span></td>
                                        <td>
                                            @if (!empty($domain))
                                                <span class="badge badge-light border badge-tech">{{ $domain }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-light border badge-tech">{{ $period }} năm</span></td>
                                        <td class="text-right price-cell">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Tổng kết -->
                    <div class="summary-box mt-4">
                        <div class="line">
                            <span>Tạm tính</span>
                            <span class="price-cell">{{ number_format($order->subtotal, 0, ',', '.') }} đ</span>
                        </div>
                        @if ($order->discount_amount > 0)
                            <div class="line">
                                <span>Giảm giá</span>
                                <span class="price-cell text-success">-{{ number_format($order->discount_amount, 0, ',', '.') }} đ</span>
                            </div>
                        @endif
                        @if ($order->tax_amount > 0)
                            <div class="line">
                                <span>Thuế VAT</span>
                                <span class="price-cell">{{ number_format($order->tax_amount, 0, ',', '.') }} đ</span>
                            </div>
                        @endif
                        <div class="line total">
                            <span>Tổng cộng</span>
                            <span class="price-cell">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dịch vụ đã bàn giao -->
            <div class="card tech-card mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="m-0"><i class="fa fa-cubes mr-1" style="color:var(--tech-accent);"></i> Dịch vụ của đơn hàng</h5>
                </div>
                <div class="card-body">
                    @foreach ($order->items as $item)
                        @php
                            $provision = $provisions[$item->id] ?? null;
                            $isSsl = ($item->product->type ?? null) === 'ssl';
                        @endphp
                        <div class="service-row">
                            <span class="icon"><i class="fa {{ $isSsl ? 'fa-lock' : 'fa-cube' }}"></i></span>
                            <div class="service-body">
                                <div class="service-name">{{ $item->name }}</div>
                                <div class="small text-muted">
                                    @if ($provision && $provision->provision_status === 'completed')
                                        <i class="fa fa-check-circle text-success"></i>
                                        Đã kích hoạt{{ $provision->provisioned_at ? ' ngày ' . $provision->provisioned_at->format('d/m/Y') : '' }}
                                        @if ($isSsl)
                                            — tải chứng chỉ ở trang chi tiết dịch vụ
                                        @endif
                                    @elseif ($provision)
                                        <i class="fa fa-clock-o text-warning"></i>
                                        Đang cung cấp dịch vụ, vui lòng chờ trong giây lát
                                    @else
                                        <i class="fa fa-hourglass-half text-muted"></i>
                                        Chưa được cung cấp — sẽ kích hoạt sau khi đơn hàng được duyệt
                                    @endif
                                </div>
                            </div>
                            @if ($provision && $provision->provision_status === 'completed')
                                <a href="{{ route('customer.services.provision.show', $provision->id) }}"
                                    class="btn btn-sm btn-tech">
                                    <i class="fa {{ $isSsl ? 'fa-download' : 'fa-cog' }}"></i>
                                    {{ $isSsl ? 'Tải chứng chỉ' : 'Quản lý dịch vụ' }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Thanh toán -->
            @if ($order->payments->isNotEmpty() || $order->invoice)
                <div class="card tech-card mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="m-0"><i class="fa fa-credit-card mr-1" style="color:var(--tech-accent);"></i> Thanh toán</h5>
                    </div>
                    <div class="card-body">
                        @if ($order->payments->isNotEmpty())
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted" style="font-size:.76rem; text-transform:uppercase;">
                                            <th>Thời gian</th>
                                            <th>Hình thức</th>
                                            <th>Trạng thái</th>
                                            <th class="text-right">Số tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->payments as $payment)
                                            @php
                                                $methodLabel = match ($payment->payment_method) {
                                                    'bank' => 'Chuyển khoản ngân hàng',
                                                    'wallet' => 'Số dư tài khoản',
                                                    'cash' => 'Tiền mặt',
                                                    default => ucfirst((string) $payment->payment_method),
                                                };
                                                $payStatus = match ($payment->status) {
                                                    'completed' => ['Thành công', 'success'],
                                                    'pending' => ['Chờ xác nhận', 'warning'],
                                                    'failed' => ['Thất bại', 'danger'],
                                                    default => [ucfirst((string) $payment->status), 'secondary'],
                                                };
                                            @endphp
                                            <tr>
                                                <td class="small">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="small">{{ $methodLabel }}</td>
                                                <td><span class="badge badge-{{ $payStatus[1] }} badge-tech">{{ $payStatus[0] }}</span></td>
                                                <td class="text-right price-cell">{{ number_format($payment->amount, 0, ',', '.') }} đ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($order->invoice)
                            {{-- Đã trả tiền thì có biên nhận; chưa trả thì chỉ có đề nghị thanh toán. --}}
                            @if ($order->invoice->status === 'paid')
                                <a href="{{ route('invoice.download', $order->invoice->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-file-pdf-o"></i> Tải biên nhận thanh toán
                                </a>
                            @else
                                <a href="{{ route('invoice.paymentRequest', $order->invoice->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa fa-file-pdf-o"></i> Tải đề nghị thanh toán
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
