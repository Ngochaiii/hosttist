@extends('layouts.web.default')

@section('content')
    <section class="price_section layout_padding">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>{{ $category->name }} Pricing</h2>
            </div>
            <div class="row g-4 mb-5">
                @forelse($products as $product)
                    <div class="col-md-6 col-lg-4">
                        <div
                            class="card h-100 shadow-sm border-{{ $product->is_featured ? 'primary' : 'light' }} {{ $product->is_featured ? 'border-2' : '' }}">
                            <!-- Badge giảm giá -->
                            @if ($product->sale_price && $product->price > $product->sale_price)
                                @php
                                    $discount = round(100 - ($product->sale_price * 100) / $product->price);
                                @endphp
                                <div class="position-absolute top-0 end-0 pt-2 pe-2">
                                    <span class="badge rounded-pill bg-danger">
                                        GIẢM {{ $discount }}%
                                    </span>
                                </div>
                            @endif

                            <!-- Badge sản phẩm nổi bật -->
                            @if ($product->is_featured)
                                <div class="card-header bg-primary text-white text-center">
                                    <small><i class="fas fa-star me-1"></i> GÓI PHỔ BIẾN</small>
                                </div>
                            @endif

                            <div class="card-body d-flex flex-column">
                                <!-- Tên sản phẩm -->
                                <h5 class="card-title text-center mb-4">{{ $product->name }}</h5>

                                <!-- Giá -->
                                <div class="text-center mb-4">
                                    @if ($product->price > $product->sale_price && $product->sale_price)
                                        <p class="text-muted mb-0">
                                            <del>{{ number_format($product->price, 0, ',', '.') }}Đ</del></p>
                                    @endif
                                    <h3 class="text-primary mb-0">
                                        {{ number_format($product->sale_price ?: $product->price, 0, ',', '.') }}Đ
                                        @if ($product->is_recurring && $product->recurring_period)
                                            <small class="text-muted">
                                                /{{ $product->recurring_period == 12 ? 'năm' : ($product->recurring_period == 1 ? 'tháng' : $product->recurring_period . ' tháng') }}
                                            </small>
                                        @endif
                                    </h3>
                                </div>

                                <!-- Tính năng -->
                                @php
                                    $features = [];

                                    if ($product->meta_data) {
                                        if (is_string($product->meta_data)) {
                                            $metaData = json_decode($product->meta_data, true);
                                        } else {
                                            $metaData = $product->meta_data;
                                        }

                                        if (
                                            is_array($metaData) &&
                                            isset($metaData['features']) &&
                                            is_array($metaData['features'])
                                        ) {
                                            $features = $metaData['features'];
                                        }
                                    }

                                    if (empty($features) && $product->description) {
                                        preg_match_all(
                                            '/[\-\*]\s*([^\n\r]+)/',
                                            strip_tags($product->description),
                                            $matches,
                                        );
                                        if (!empty($matches[1])) {
                                            $features = array_slice($matches[1], 0, 7);
                                        }
                                    }

                                    if (empty($features) && $product->short_description) {
                                        $sentences = array_filter(
                                            array_map('trim', explode('.', $product->short_description)),
                                            function ($item) {
                                                return !empty($item);
                                            },
                                        );
                                        if (count($sentences) > 0) {
                                            $features = array_slice($sentences, 0, 7);
                                        }
                                    }

                                    if ($product->is_recurring && $product->recurring_period) {
                                        if ($product->recurring_period == 12) {
                                            $features[] = 'Bảo hành 1 năm';
                                        } elseif ($product->recurring_period == 36) {
                                            $features[] = 'Bảo hành mở rộng 3 năm';
                                        } elseif ($product->recurring_period == 60) {
                                            $features[] = 'Bảo hành cao cấp 5 năm';
                                            $features[] = 'Hỗ trợ kỹ thuật ưu tiên';
                                        }
                                    }
                                @endphp

                                <ul class="list-group list-group-flush mb-4">
                                    @foreach ($features as $feature)
                                        <li class="list-group-item border-0 ps-0">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>

                                <!-- Nút đặt hàng -->
                                <div class="mt-auto">
                                    <a href="{{ route('service.detail', $product->slug) }}"
                                        class="btn btn-{{ $product->is_featured ? 'primary' : 'outline-primary' }} w-100 mb-2">
                                        Đặt ngay <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                    <a href="{{ route('service.detail', $product->slug) }}"
                                        class="btn btn-link text-muted w-100">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 py-5">
                        <div class="text-center">
                            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                            <h4>Không tìm thấy sản phẩm</h4>
                            <p class="text-muted">Hiện không có sản phẩm nào trong danh mục này. Vui lòng quay lại sau.</p>
                            <a href="{{ route('homepage') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-home me-2"></i> Quay lại trang chủ
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
