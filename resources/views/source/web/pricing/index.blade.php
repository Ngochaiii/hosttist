@extends('layouts.web.default')

@section('content')
    <section class="products-section py-5">
        <div class="container">
            <!-- Header -->
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Bảng Giá Dịch Vụ</h2>
                <p class="section-subtitle">Chọn gói phù hợp với nhu cầu của bạn</p>
            </div>

            <!-- Category Tabs -->
            <ul class="nav nav-pills category-nav mb-4 justify-content-center" role="tablist">
                @foreach ($categories as $index => $category)
                    @if ($category->products->count() > 0)
                        <li class="nav-item">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" data-toggle="pill"
                                href="#category-{{ $category->id }}">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                @foreach ($categories as $index => $category)
                    @if ($category->products->count() > 0)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="category-{{ $category->id }}">

                            @if ($category->description)
                                <div class="category-intro text-center mb-4">
                                    <p class="text-muted">{{ $category->description }}</p>
                                </div>
                            @endif

                            <div class="row justify-content-center">
                                @foreach ($category->products as $product)
                                    <div class="col-lg-4 col-md-6 mb-4">
                                        <div class="price-card {{ $product->is_featured ? 'featured' : '' }}">
                                            @if ($product->is_featured)
                                                <div class="featured-badge">Phổ biến</div>
                                            @endif

                                            <div class="card-icon">
                                                <i class="fas fa-gem"></i>
                                            </div>

                                            <h3 class="plan-name">{{ $product->name }}</h3>

                                            <div class="plan-price">
                                                @php
                                                    $displayPrice = $product->sale_price ?: $product->price;
                                                @endphp
                                                <span class="amount">{{ number_format($displayPrice, 0, ',', '.') }}
                                                    đ</span>
                                                <span class="period">/Tháng</span>
                                            </div>

                                            @if ($product->short_description)
                                                <p class="plan-desc">{{ Str::limit($product->short_description, 80) }}</p>
                                            @endif

                                            <div class="plan-features">
                                                @php
                                                    // Parse options or meta_data for features
                                                    $features = [];
                                                    if ($product->options) {
                                                        $options = is_string($product->options)
                                                            ? json_decode($product->options, true)
                                                            : $product->options;
                                                        if (is_array($options)) {
                                                            $features = array_slice($options, 0, 4);
                                                        }
                                                    }

                                                    // Fallback features based on product type
                                                    if (empty($features)) {
                                                        $features = match ($product->type) {
                                                            'ssl' => [
                                                                'Mã hóa 256-bit',
                                                                'Tương thích 99%',
                                                                'Hỗ trợ 24/7',
                                                                'Bảo mật tối ưu',
                                                            ],
                                                            'vps' => [
                                                                'CPU hiệu năng cao',
                                                                'RAM nâng cấp linh hoạt',
                                                                'SSD NVMe tốc độ cao',
                                                                'Băng thông không giới hạn',
                                                            ],
                                                            'hosting' => [
                                                                'Dung lượng SSD',
                                                                'Email không giới hạn',
                                                                'SSL miễn phí',
                                                                'Backup hàng ngày',
                                                            ],
                                                            'domain' => [
                                                                'Đăng ký nhanh chóng',
                                                                'Quản lý DNS miễn phí',
                                                                'Chuyển đổi dễ dàng',
                                                                'Hỗ trợ WHOIS',
                                                            ],
                                                            default => [
                                                                'Hiệu suất cao',
                                                                'Hỗ trợ 24/7',
                                                                'Bảo mật tốt',
                                                                'Dễ sử dụng',
                                                            ],
                                                        };
                                                    }
                                                @endphp

                                                <ul>
                                                    @foreach (array_slice($features, 0, 4) as $feature)
                                                        <li>
                                                            <i class="fas fa-check"></i>
                                                            {{ is_array($feature) ? $feature['name'] ?? ($feature['label'] ?? 'Feature') : $feature }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <a href="{{ route('service.detail', $product->slug) }}" class="btn-select">
                                                Thêm vào giỏ hàng
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endsection

@push('header_css')
    <style>
        :root {
            --primary: #5bb5a2;
            --primary-dark: #4a9b8e;
            --featured-bg: #1a1a2e;
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
            --border: #e9ecef;
        }

        .products-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            min-height: 100vh;
        }

        .section-title {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 1rem;
            color: var(--text-muted);
        }

        /* Tabs */
        .category-nav {
            border: none;
            gap: 0.75rem;
        }

        .category-nav .nav-link {
            background: #fff;
            border: 2px solid var(--border);
            color: var(--text-dark);
            padding: 0.65rem 1.75rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .category-nav .nav-link:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .category-nav .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-color: var(--primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(91, 181, 162, 0.3);
        }

        /* Price Card */
        .price-card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem 1.75rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid var(--border);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .price-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            border-color: var(--primary);
        }

        /* Featured Card */
        .price-card.featured {
            background: linear-gradient(135deg, var(--featured-bg) 0%, #16213e 100%);
            border: none;
            transform: scale(1.03);
        }

        .price-card.featured:hover {
            transform: scale(1.03) translateY(-5px);
        }

        .featured-badge {
            position: absolute;
            top: 18px;
            right: -28px;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: var(--featured-bg);
            padding: 4px 35px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            transform: rotate(45deg);
            letter-spacing: 0.5px;
        }

        .card-icon i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .price-card.featured .card-icon i {
            color: #ffd700;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            min-height: 2em;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .price-card.featured .plan-name {
            color: #fff;
        }

        .plan-price {
            margin-bottom: 1rem;
        }

        .plan-price .amount {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            display: block;
        }

        .price-card.featured .plan-price .amount {
            color: #ffd700;
        }

        .plan-price .period {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .price-card.featured .plan-price .period {
            color: rgba(255, 255, 255, 0.7);
        }

        .plan-desc {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            min-height: 2.5em;
            line-height: 1.4;
        }

        .price-card.featured .plan-desc {
            color: rgba(255, 255, 255, 0.75);
        }

        /* Features */
        .plan-features {
            flex-grow: 1;
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .price-card.featured .plan-features {
            border-top-color: rgba(255, 255, 255, 0.15);
        }

        .plan-features ul {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }

        .plan-features li {
            padding: 0.5rem 0;
            color: var(--text-dark);
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .price-card.featured .plan-features li {
            color: rgba(255, 255, 255, 0.9);
        }

        .plan-features li i {
            color: var(--primary);
            font-size: 0.75rem;
            margin-top: 0.25rem;
            flex-shrink: 0;
        }

        .price-card.featured .plan-features li i {
            color: #ffd700;
        }

        /* Button */
        .btn-select {
            display: block;
            width: 100%;
            padding: 0.85rem 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-select:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(91, 181, 162, 0.4);
        }

        .price-card.featured .btn-select {
            background: #fff;
            color: var(--featured-bg);
        }

        .price-card.featured .btn-select:hover {
            background: var(--primary);
            color: #fff;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .price-card.featured {
                transform: scale(1);
            }

            .section-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 767px) {
            .section-title {
                font-size: 1.75rem;
            }

            .price-card {
                padding: 1.75rem 1.5rem;
            }

            .card-icon i {
                font-size: 2.5rem;
            }

            .plan-name {
                font-size: 1.35rem;
            }

            .plan-price .amount {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 575px) {
            .category-nav .nav-link {
                padding: 0.5rem 1.25rem;
                font-size: 0.875rem;
            }
        }
    </style>
@endpush

@push('footer_js')
    <script>
        $(document).ready(function() {
            $('.category-nav a[data-toggle="pill"]').on('shown.bs.tab', function() {
                $('html, body').animate({
                    scrollTop: $('.tab-content').offset().top - 80
                }, 400);
            });
        });
    </script>
@endpush
