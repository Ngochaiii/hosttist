@extends('layouts.web.default')

@section('content')
    <section class="cart_section layout_padding">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>Giỏ hàng của bạn</h2>
            </div>

            <div class="row mt-4">
                <div class="col-lg-8">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($cart && $cart->items->count() > 0)
                        <div class="cart_items card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Sản phẩm/Dịch vụ</th>
                                                <th>Đơn giá</th>
                                                <th>Số lượng</th>
                                                <th>Thành tiền</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Trong view giỏ hàng -->
                                            @foreach ($cart->items as $item)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $item->name }}</strong>
                                                        @php
                                                            $options = json_decode($item->options, true) ?: [];
                                                            $period = $options['period'] ?? 1;
                                                            $autoRenew = $options['auto_renew'] ?? false;
                                                        @endphp
                                                        <div class="small text-muted mt-2">
                                                            @foreach ($options as $key => $value)
                                                                @if (!in_array($key, ['service_type', 'period']))
                                                                    <div>
                                                                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                                                        {{ $value }}
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                        <div class="small text-muted">Thời hạn: {{ $period }} năm
                                                        </div>
                                                        @if ($autoRenew)
                                                            <div class="small text-muted">Tự động gia hạn: Có</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php $options = json_decode($item->options, true) ?: []; @endphp
                                                        <div>Thời hạn: {{ $options['period'] ?? 1 }} năm</div>

                                                        @if ($item->product->type == 'ssl' || $item->product->type == 'domain')
                                                            <div class="mt-1">
                                                                <span class="badge bg-info text-white">Domain:
                                                                    {{ $options['domain'] ?? 'N/A' }}</span>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $item->formatted_unit_price }}</td>
                                                    <td>
                                                        <form action="{{ route('cart.update', $item->id) }}" method="post"
                                                            class="d-flex align-items-center">
                                                            @csrf
                                                            <input type="number" name="quantity"
                                                                value="{{ $item->quantity }}" min="1" max="100"
                                                                class="form-control form-control-sm" style="width: 70px">
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-primary ml-2">
                                                                <i class="fa fa-refresh"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>{{ $item->formatted_subtotal }}</td>
                                                    <td>
                                                        <form action="{{ route('cart.remove', $item->id) }}"
                                                            method="post">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3 text-right">
                                    <form action="{{ route('cart.clear') }}" method="post">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger">Xóa tất cả</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fa fa-shopping-cart fa-4x text-muted mb-3"></i>
                                <h4>Giỏ hàng trống</h4>
                                <p class="text-muted">Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                                <a href="{{ route('homepage') }}" class="btn btn-primary mt-3">Tiếp tục mua sắm</a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    @if ($cart && $cart->items->count() > 0)
                        <div class="cart_summary card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="m-0">Tóm tắt đơn hàng</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tạm tính:</span>
                                    <span>{{ $cart->formatted_subtotal }}</span>
                                </div>

                                @if ($cart->tax_amount > 0)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Thuế:</span>
                                        <span>{{ number_format($cart->tax_amount, 0, ',', '.') }} đ</span>
                                    </div>
                                @endif

                                @if ($cart->discount_amount > 0)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Giảm giá:</span>
                                        <span>-{{ number_format($cart->discount_amount, 0, ',', '.') }} đ</span>
                                    </div>
                                @endif

                                <hr>

                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Tổng cộng:</strong>
                                    <strong class="text-primary">{{ $cart->formatted_total }}</strong>
                                </div>

                                <div class="action_buttons">
                                    <a href="{{ route('quote') }}" class="btn btn-primary btn-block">Tiến hành thanh
                                        toán</a>
                                    <a href="{{ route('homepage') }}" class="btn btn-outline-secondary btn-block mt-2">Tiếp
                                        tục mua sắm</a>
                                </div>
                            </div>
                        </div>
                        {{--
                <div class="coupon_box card mt-3">
                    <div class="card-body">
                        <h5>Mã giảm giá</h5>
                        <form action="{{ route('coupon.apply') }}" method="post" class="mt-3">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control" placeholder="Nhập mã giảm giá">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-outline-primary">Áp dụng</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div> --}}
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
