@extends('layouts.web.default')

@section('content')
<style>
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
        padding: 28px 28px 24px;
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
    }
    .order-tech .btn-back:hover { background: rgba(255, 255, 255, .2); color: #fff; }

    /* Stepper */
    .order-tech .tech-stepper { display: flex; align-items: center; margin-top: 22px; }
    .order-tech .tech-step { display: flex; align-items: center; flex: 1; }
    .order-tech .tech-step .dot {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255, 255, 255, .1); border: 2px solid rgba(255, 255, 255, .25);
        font-size: .8rem; flex-shrink: 0; color: rgba(255,255,255,.7);
    }
    .order-tech .tech-step .label { margin-left: 10px; font-size: .82rem; color: rgba(255, 255, 255, .65); white-space: nowrap; }
    .order-tech .tech-step .line { flex: 1; height: 2px; background: rgba(255, 255, 255, .18); margin: 0 10px; }
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
        gap: 12px;
        height: 100%;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .order-tech .info-tile:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(99, 102, 241, .12); }
    .order-tech .info-tile .icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--tech-accent), var(--tech-accent-2));
        color: #fff; font-size: 1rem; flex-shrink: 0;
    }
    .order-tech .info-tile .tile-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; margin-bottom: 2px; }
    .order-tech .info-tile .tile-value { font-weight: 600; color: #0f172a; word-break: break-word; }

    /* Product table */
    .order-tech .tech-table thead th {
        background: #0f172a; color: #e2e8f0; border: none;
        font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 600;
        padding: 12px 14px;
    }
    .order-tech .tech-table tbody td { padding: 14px; vertical-align: middle; border-color: rgba(15, 23, 42, .06); }
    .order-tech .tech-table tbody tr:hover { background: rgba(99, 102, 241, .04); }
    .order-tech .product-avatar {
        width: 34px; height: 34px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(99, 102, 241, .1); color: var(--tech-accent);
        margin-right: 8px; flex-shrink: 0;
    }
    .order-tech .badge-tech {
        border-radius: 999px; font-weight: 500; padding: 5px 10px; font-size: .74rem;
    }
    .order-tech .price-cell { font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace; font-weight: 600; color: #0f172a; }

    /* SSL / private data panel */
    .order-tech .terminal-card { background: #0b1120; border-radius: 14px; overflow: hidden; border: 1px solid rgba(34, 211, 238, .25); }
    .order-tech .terminal-header {
        background: #111827; padding: 10px 16px; display: flex; align-items: center; gap: 8px;
        border-bottom: 1px solid rgba(34, 211, 238, .15);
    }
    .order-tech .terminal-header .dot-btn { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .order-tech .terminal-title { color: #cbd5e1; font-size: .8rem; margin-left: 6px; font-family: 'SFMono-Regular', Consolas, monospace; }
    .order-tech .terminal-body { padding: 16px; }
    .order-tech .terminal-body textarea {
        background: #020617; color: #5eead4; border: 1px solid rgba(94, 234, 212, .18);
        font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace; font-size: .82rem;
        border-radius: 8px;
    }
    .order-tech .terminal-body textarea:focus { box-shadow: none; border-color: rgba(94, 234, 212, .4); }
    .order-tech .btn-copy-tech {
        background: linear-gradient(135deg, var(--tech-accent), var(--tech-accent-2));
        border: none; color: #fff;
    }
    .order-tech .btn-copy-tech:hover { color: #fff; opacity: .9; }
</style>

<div class="container py-5 order-tech">
    <div class="row">
        @include('source.web.profile.partials.sidebar')
        <div class="col-md-8">
            <div class="card tech-card mb-4">
                <div class="tech-hero">
                    <div class="d-flex justify-content-between align-items-start position-relative">
                        <div>
                            <div class="text-uppercase small mb-1" style="letter-spacing:.08em; color:rgba(255,255,255,.6);">
                                <i class="fa fa-microchip"></i> Chi tiết đơn hàng
                            </div>
                            <h4 class="mb-2">Đơn hàng <span class="order-code">#{{ $order->order_number }}</span></h4>
                            <div class="small" style="color:rgba(255,255,255,.6);">
                                <i class="fa fa-clock-o"></i> {{ $order->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                        <a href="{{ route('customer.orders') }}" class="btn btn-sm btn-back position-relative">
                            <i class="fa fa-arrow-left"></i> Quay lại
                        </a>
                    </div>

                    @php
                        $statusSteps = ['pending' => 1, 'processing' => 2, 'completed' => 3];
                        $currentStep = $statusSteps[$order->status] ?? 0;
                        $isCancelledLike = $currentStep === 0;
                    @endphp
                    @unless($isCancelledLike)
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
                            <div class="tech-step cancelled">
                                <span class="dot"><i class="fa fa-times"></i></span>
                                <span class="label">{{ ucfirst($order->status) }}</span>
                            </div>
                        </div>
                    @endunless
                </div>

                <div class="card-body">
                    <!-- Thông tin đơn hàng cơ bản -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="info-tile">
                                <span class="icon"><i class="fa fa-hashtag"></i></span>
                                <div>
                                    <div class="tile-label">Mã đơn hàng</div>
                                    <div class="tile-value">{{ $order->order_number }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="info-tile">
                                <span class="icon"><i class="fa fa-money"></i></span>
                                <div>
                                    <div class="tile-label">Tổng tiền</div>
                                    <div class="tile-value">{{ number_format($order->total_amount, 0, ',', '.') }} đ</div>
                                </div>
                            </div>
                        </div>
                        @if($order->invoice)
                            <div class="col-6 col-md-3">
                                <div class="info-tile">
                                    <span class="icon"><i class="fa fa-file-text-o"></i></span>
                                    <div>
                                        <div class="tile-label">Mã hóa đơn</div>
                                        <div class="tile-value">{{ $order->invoice->invoice_number }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="info-tile">
                                    <span class="icon"><i class="fa fa-check-circle-o"></i></span>
                                    <div>
                                        <div class="tile-label">Hóa đơn</div>
                                        <div class="tile-value">
                                            @if ($order->invoice->status == 'paid')
                                                <span class="badge bg-success badge-tech">Đã thanh toán</span>
                                            @elseif($order->invoice->status == 'pending')
                                                <span class="badge bg-warning badge-tech">Chờ thanh toán</span>
                                            @else
                                                <span class="badge bg-secondary badge-tech">{{ ucfirst($order->invoice->status) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Chi tiết sản phẩm đã mua -->
                    <h5 class="mt-4 mb-3"><i class="fa fa-shopping-cart me-1" style="color:var(--tech-accent);"></i> Sản phẩm đã mua</h5>
                    <div class="table-responsive">
                        <table class="table tech-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Sản phẩm</th>
                                    <th>Loại</th>
                                    <th>Domain</th>
                                    <th>Đơn giá</th>
                                    <th>Thời hạn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @php
                                            $typeIcon = match($item->product->type ?? null) {
                                                'ssl' => 'fa-lock',
                                                'domain' => 'fa-globe',
                                                'hosting' => 'fa-server',
                                                default => 'fa-cube',
                                            };
                                        @endphp
                                        <span class="product-avatar"><i class="fa {{ $typeIcon }}"></i></span>{{ $item->name }}
                                    </td>
                                    <td>
                                        @if($item->product)
                                            @if($item->product->type == 'ssl')
                                                <span class="badge bg-success badge-tech">SSL</span>
                                            @elseif($item->product->type == 'domain')
                                                <span class="badge bg-primary badge-tech">Domain</span>
                                            @elseif($item->product->type == 'hosting')
                                                <span class="badge bg-info badge-tech">Hosting</span>
                                            @else
                                                <span class="badge bg-secondary badge-tech">{{ ucfirst($item->product->type) }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $domain = $item->domain ?? '';
                                            if(empty($domain)) {
                                                $options = json_decode($item->options, true) ?: [];
                                                $domain = $options['domain'] ?? '';
                                            }
                                        @endphp

                                        @if(!empty($domain))
                                            <span class="badge bg-info text-white badge-tech">{{ $domain }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="price-cell">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                                    <td>
                                        @php
                                            $period = \App\Helpers\ServiceHelper::orderItemDurationYears($item);
                                        @endphp
                                        <span class="badge bg-light text-dark border badge-tech">{{ $period }} năm</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Chi tiết dữ liệu meta_data (khóa private/chứng chỉ) - CHỈ cho sản phẩm SSL -->
                    @foreach ($order->items as $index => $item)
                        @if($item->product && $item->product->type === 'ssl')
                            @php
                                // Lấy trực tiếp data từ database thay vì qua model để tránh vấn đề accessor
                                $rawProduct = DB::table('products')->select('meta_data', 'name')->where('id', $item->product->id)->first();
                            @endphp

                            @if($rawProduct && $rawProduct->meta_data)
                                <div class="terminal-card mt-4">
                                    <div class="terminal-header">
                                        <span class="dot-btn" style="background:#ef4444;"></span>
                                        <span class="dot-btn" style="background:#f59e0b;"></span>
                                        <span class="dot-btn" style="background:#22c55e;"></span>
                                        <span class="terminal-title"><i class="fa fa-lock me-1"></i>{{ $rawProduct->name }} — meta_data.json</span>
                                    </div>
                                    <div class="terminal-body">
                                        <div class="position-relative">
                                            <textarea class="form-control" id="meta_data_{{ $index }}"
                                                      rows="15" readonly>{{ $rawProduct->meta_data }}</textarea>
                                            <button class="btn btn-sm btn-copy-tech position-absolute top-0 end-0 m-2 copy-text"
                                                    data-target="meta_data_{{ $index }}">
                                                <i class="fa fa-copy"></i> Sao chép
                                            </button>
                                        </div>
                                        <small class="form-text text-muted mt-2 d-block">
                                            <i class="fa fa-shield"></i> Dữ liệu meta có thể chứa các khóa private, chứng chỉ SSL...
                                        </small>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast thông báo copy -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="copyToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-check-circle me-2"></i> Đã sao chép vào clipboard!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('footer_js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo toast
        const copyToast = new bootstrap.Toast(document.getElementById('copyToast'), {
            delay: 2000
        });

        // Xử lý nút sao chép văn bản
        const copyButtons = document.querySelectorAll('.copy-text');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const textArea = document.getElementById(targetId);

                // Chọn toàn bộ văn bản
                textArea.select();
                textArea.setSelectionRange(0, 99999); // Cho mobile

                // Sao chép
                document.execCommand('copy');

                // Hiển thị thông báo
                copyToast.show();
            });
        });
    });
</script>
@endpush
