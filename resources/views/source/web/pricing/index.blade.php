@extends('layouts.web.default')

@section('content')
    @php
        $iconMap = [
            'ssl'                          => ['icon' => 'fas fa-shield-alt',   'color' => '#10b981'],
            'cloud-hosting'                => ['icon' => 'fas fa-cloud',        'color' => '#3b82f6'],
            'cloud_hosting'                => ['icon' => 'fas fa-cloud',        'color' => '#3b82f6'],
            'hosting'                      => ['icon' => 'fas fa-server',       'color' => '#4154f1'],
            'domain'                       => ['icon' => 'fas fa-globe',        'color' => '#f59e0b'],
            'design'                       => ['icon' => 'fas fa-paint-brush',  'color' => '#8b5cf6'],
            'design-website'               => ['icon' => 'fas fa-paint-brush',  'color' => '#8b5cf6'],
            'design-uiux'                  => ['icon' => 'fas fa-pen-ruler',    'color' => '#ec4899'],
            'web_design'                   => ['icon' => 'fas fa-paint-brush',  'color' => '#8b5cf6'],
            'anti-ddos'                    => ['icon' => 'fas fa-shield-virus', 'color' => '#ef4444'],
            'anti_ddos'                    => ['icon' => 'fas fa-shield-virus', 'color' => '#ef4444'],
            'vps'                          => ['icon' => 'fas fa-microchip',    'color' => '#06b6d4'],
            'reseller'                     => ['icon' => 'fas fa-store',        'color' => '#14b8a6'],
            'reseller-cloud-vps-vpc'       => ['icon' => 'fas fa-store',        'color' => '#14b8a6'],
            'email'                        => ['icon' => 'fas fa-envelope',     'color' => '#6366f1'],
            'support'                      => ['icon' => 'fas fa-headset',      'color' => '#0ea5e9'],
            'support-facebook-all-service' => ['icon' => 'fab fa-facebook',     'color' => '#1877f2'],
            'advertising'                  => ['icon' => 'fab fa-facebook',     'color' => '#1877f2'],
            'seo'                          => ['icon' => 'fas fa-magnifying-glass-chart', 'color' => '#22c55e'],
        ];
        $pickIcon = function ($slug, $type = null) use ($iconMap) {
            $keys = array_filter([strtolower($slug ?? ''), strtolower($type ?? '')]);
            foreach ($keys as $k) {
                if (isset($iconMap[$k])) return $iconMap[$k];
                foreach ($iconMap as $mapKey => $val) {
                    if ($k && str_contains($k, $mapKey)) return $val;
                }
            }
            return ['icon' => 'fas fa-cube', 'color' => '#4154f1'];
        };
    @endphp

    <section class="pricing-page">
        <div class="container">
            <div class="pricing-header">
                <span class="pricing-badge">Bảng giá</span>
                <h1 class="pricing-title">Bảng Giá Dịch Vụ</h1>
                <p class="pricing-subtitle">Chọn gói phù hợp với nhu cầu của bạn — minh bạch, không phí ẩn, hỗ trợ 24/7.</p>
            </div>

            <ul class="nav nav-pills category-nav" role="tablist">
                @foreach ($categories as $index => $category)
                    @if ($category->products->count() > 0)
                        @php $cIcon = $pickIcon($category->slug); @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" data-toggle="pill"
                                href="#category-{{ $category->id }}">
                                <i class="{{ $cIcon['icon'] }}"></i>
                                {{ $category->name }}
                                <span class="tab-count">{{ $category->products->count() }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach ($categories as $index => $category)
                    @if ($category->products->count() > 0)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="category-{{ $category->id }}">

                            @if ($category->description)
                                <div class="category-intro">
                                    <p>{{ Str::limit(strip_tags($category->description), 220) }}</p>
                                </div>
                            @endif

                            <div class="row justify-content-center">
                                @foreach ($category->products as $product)
                                    @php
                                        $meta = $pickIcon($category->slug, $product->type);
                                        $displayPrice = $product->sale_price ?: $product->price;
                                        $hasSale = $product->sale_price && $product->price > $product->sale_price;
                                        $savePct = $hasSale ? round((($product->price - $product->sale_price) / $product->price) * 100) : 0;
                                        $period = match (true) {
                                            !($product->is_recurring ?? false) => '/trọn gói',
                                            ($product->recurring_period ?? 1) == 12 => '/năm',
                                            ($product->recurring_period ?? 1) == 1 => '/tháng',
                                            default => '/' . $product->recurring_period . ' tháng',
                                        };
                                    @endphp
                                    <div class="col-lg-4 col-md-6 mb-4 d-flex">
                                        <div class="price-card {{ $product->is_featured ? 'featured' : '' }}">
                                            @if ($product->is_featured)
                                                <div class="featured-badge">
                                                    <i class="fas fa-star"></i> Phổ biến
                                                </div>
                                            @endif

                                            <div class="card-icon"
                                                @if (!$product->is_featured) style="background: {{ $meta['color'] }}1a; color: {{ $meta['color'] }};" @endif>
                                                <i class="{{ $meta['icon'] }}"></i>
                                            </div>

                                            <h3 class="plan-name">{{ $product->name }}</h3>

                                            @if ($product->short_description)
                                                <p class="plan-desc">{{ Str::limit($product->short_description, 90) }}</p>
                                            @endif

                                            <div class="plan-price">
                                                <div class="price-row">
                                                    <span class="amount">{{ number_format($displayPrice, 0, ',', '.') }}</span>
                                                    <span class="currency">₫</span>
                                                    <span class="period">{{ $period }}</span>
                                                </div>
                                                @if ($hasSale)
                                                    <div class="original-price">
                                                        <del>{{ number_format($product->price, 0, ',', '.') }}₫</del>
                                                        <span class="save-pill">-{{ $savePct }}%</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="plan-features">
                                                @php
                                                    $features = [];
                                                    if ($product->options) {
                                                        $options = is_string($product->options)
                                                            ? json_decode($product->options, true)
                                                            : $product->options;
                                                        if (is_array($options)) {
                                                            $features = array_slice($options, 0, 5);
                                                        }
                                                    }

                                                    if (empty($features)) {
                                                        $features = match ($product->type) {
                                                            'ssl' => [
                                                                'Mã hóa 256-bit',
                                                                'Tương thích 99%',
                                                                'Hỗ trợ 24/7',
                                                                'Bảo mật tối ưu',
                                                                'Cài đặt nhanh chóng',
                                                            ],
                                                            'vps' => [
                                                                'CPU hiệu năng cao',
                                                                'RAM nâng cấp linh hoạt',
                                                                'SSD NVMe tốc độ cao',
                                                                'Băng thông không giới hạn',
                                                                'Toàn quyền root',
                                                            ],
                                                            'hosting' => [
                                                                'Dung lượng SSD',
                                                                'Email không giới hạn',
                                                                'SSL miễn phí',
                                                                'Backup hàng ngày',
                                                                'cPanel quản lý',
                                                            ],
                                                            'domain' => [
                                                                'Đăng ký nhanh chóng',
                                                                'Quản lý DNS miễn phí',
                                                                'Chuyển đổi dễ dàng',
                                                                'Hỗ trợ WHOIS',
                                                                'Auto-renewal',
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
                                                    @foreach (array_slice($features, 0, 5) as $feature)
                                                        <li>
                                                            <span class="check-icon"><i class="fas fa-check"></i></span>
                                                            <span>{{ is_array($feature) ? ($feature['name'] ?? ($feature['label'] ?? 'Feature')) : $feature }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <a href="{{ route('service.detail', $product->slug) }}" class="btn-select">
                                                <i class="fas fa-arrow-right"></i>
                                                Xem chi tiết & đặt mua
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                @if ($categories->filter(fn($c) => $c->products->count() > 0)->isEmpty())
                    <div class="empty-state text-center py-5">
                        <i class="fas fa-box-open" style="font-size: 3rem; color: #c7cdf0;"></i>
                        <p class="mt-3 text-muted">Hiện chưa có sản phẩm. Vui lòng quay lại sau.</p>
                    </div>
                @endif
            </div>

            <div class="pricing-guarantee">
                <div class="guarantee-item">
                    <div class="g-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <h5>Hoàn tiền 30 ngày</h5>
                        <p>Không hài lòng? Hoàn lại 100%</p>
                    </div>
                </div>
                <div class="guarantee-item">
                    <div class="g-icon"><i class="fas fa-headset"></i></div>
                    <div>
                        <h5>Hỗ trợ 24/7</h5>
                        <p>Đội ngũ kỹ thuật luôn sẵn sàng</p>
                    </div>
                </div>
                <div class="guarantee-item">
                    <div class="g-icon"><i class="fas fa-bolt"></i></div>
                    <div>
                        <h5>Uptime 99.9%</h5>
                        <p>Hạ tầng TIER-3 ổn định</p>
                    </div>
                </div>
                <div class="guarantee-item">
                    <div class="g-icon"><i class="fas fa-sync-alt"></i></div>
                    <div>
                        <h5>Nâng/hạ gói miễn phí</h5>
                        <p>Linh hoạt theo nhu cầu</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('header_css')
    <style>
        :root {
            --pp-primary: #4154f1;
            --pp-primary-2: #667eea;
            --pp-accent: #ffd700;
            --pp-text: #1a1a1a;
            --pp-muted: #6b7280;
            --pp-border: #e9ecf5;
            --pp-bg: #f7f9ff;
        }

        .pricing-page {
            position: relative;
            padding: 70px 0 80px;
            background: linear-gradient(180deg, var(--pp-bg) 0%, #ffffff 100%);
            overflow: hidden;
        }
        .pricing-page::before,
        .pricing-page::after {
            content: '';
            position: absolute;
            width: 340px; height: 340px;
            border-radius: 50%;
            pointer-events: none;
        }
        .pricing-page::before {
            top: -120px; right: -120px;
            background: radial-gradient(circle, rgba(65, 84, 241, 0.12) 0%, transparent 70%);
        }
        .pricing-page::after {
            bottom: -100px; left: -100px;
            background: radial-gradient(circle, rgba(118, 75, 162, 0.1) 0%, transparent 70%);
        }
        .pricing-page .container { position: relative; z-index: 1; }

        /* Header */
        .pricing-header { text-align: center; margin-bottom: 3rem; }
        .pricing-badge {
            display: inline-block;
            background: rgba(65, 84, 241, 0.1);
            color: var(--pp-primary);
            padding: 6px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .pricing-title {
            font-size: 2.6rem;
            font-weight: 800;
            color: var(--pp-text);
            margin-bottom: 0.75rem;
            letter-spacing: -0.5px;
        }
        .pricing-subtitle {
            font-size: 1.05rem;
            color: var(--pp-muted);
            max-width: 620px;
            margin: 0 auto;
            line-height: 1.65;
        }

        /* Category Tabs */
        .category-nav {
            border: none;
            gap: 0.6rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }
        .category-nav .nav-link {
            background: #ffffff;
            border: 1px solid var(--pp-border);
            color: var(--pp-text);
            padding: 0.65rem 1.25rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .category-nav .nav-link i { font-size: 0.9rem; opacity: 0.85; }
        .category-nav .nav-link:hover {
            border-color: var(--pp-primary);
            color: var(--pp-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(65, 84, 241, 0.16);
        }
        .category-nav .nav-link.active {
            background: linear-gradient(135deg, var(--pp-primary), var(--pp-primary-2));
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(65, 84, 241, 0.3);
        }
        .category-nav .nav-link .tab-count {
            background: rgba(0, 0, 0, 0.06);
            color: inherit;
            padding: 1px 8px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .category-nav .nav-link.active .tab-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* Category intro */
        .category-intro {
            max-width: 760px;
            margin: 0 auto 2rem;
            text-align: center;
            color: var(--pp-muted);
            font-size: 0.95rem;
        }

        /* Price Card */
        .price-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2.25rem 2rem;
            border: 1px solid var(--pp-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
            width: 100%;
        }
        .price-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 22px 44px rgba(65, 84, 241, 0.14);
            border-color: #c7cdf0;
        }

        /* Featured Card */
        .price-card.featured {
            background: linear-gradient(160deg, #4154f1 0%, #667eea 55%, #764ba2 100%);
            border: none;
            color: #ffffff;
            box-shadow: 0 22px 44px rgba(65, 84, 241, 0.32);
            transform: translateY(-10px);
        }
        .price-card.featured:hover {
            transform: translateY(-14px);
            box-shadow: 0 28px 55px rgba(65, 84, 241, 0.42);
        }
        .featured-badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #ffd700, #ffb400);
            color: #1a1a1a;
            padding: 7px 20px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            box-shadow: 0 6px 16px rgba(255, 180, 0, 0.45);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .card-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.2rem;
            background: rgba(65, 84, 241, 0.1);
            color: var(--pp-primary);
        }
        .price-card.featured .card-icon {
            background: rgba(255, 255, 255, 0.2);
            color: var(--pp-accent);
        }

        .plan-name {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--pp-text);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        .price-card.featured .plan-name { color: #ffffff; }

        .plan-desc {
            color: var(--pp-muted);
            font-size: 0.9rem;
            line-height: 1.55;
            margin-bottom: 1.25rem;
            min-height: 2.8em;
        }
        .price-card.featured .plan-desc { color: rgba(255, 255, 255, 0.85); }

        .plan-price {
            margin-bottom: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px dashed var(--pp-border);
        }
        .price-card.featured .plan-price {
            border-bottom-color: rgba(255, 255, 255, 0.25);
        }
        .price-row {
            display: flex;
            align-items: baseline;
            gap: 4px;
            flex-wrap: wrap;
        }
        .plan-price .amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--pp-text);
            line-height: 1;
            letter-spacing: -1px;
        }
        .price-card.featured .plan-price .amount { color: #ffffff; }
        .plan-price .currency {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--pp-primary);
        }
        .price-card.featured .plan-price .currency { color: var(--pp-accent); }
        .plan-price .period {
            color: #9ca3af;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 4px;
        }
        .price-card.featured .plan-price .period { color: rgba(255, 255, 255, 0.75); }

        .original-price {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 0.9rem;
        }
        .original-price del { color: #9ca3af; }
        .price-card.featured .original-price del { color: rgba(255, 255, 255, 0.6); }
        .save-pill {
            background: #fee2e2;
            color: #dc2626;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .price-card.featured .save-pill {
            background: rgba(255, 215, 0, 0.2);
            color: var(--pp-accent);
        }

        /* Features */
        .plan-features { flex-grow: 1; margin-bottom: 1.5rem; }
        .plan-features ul {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }
        .plan-features li {
            padding: 0.5rem 0;
            color: #374151;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }
        .price-card.featured .plan-features li { color: rgba(255, 255, 255, 0.92); }
        .plan-features .check-icon {
            width: 20px;
            height: 20px;
            background: rgba(16, 185, 129, 0.14);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .plan-features .check-icon i {
            color: #10b981;
            font-size: 0.65rem;
        }
        .price-card.featured .plan-features .check-icon {
            background: rgba(255, 215, 0, 0.2);
        }
        .price-card.featured .plan-features .check-icon i {
            color: var(--pp-accent);
        }

        /* Button */
        .btn-select {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 0.9rem 1.25rem;
            background: linear-gradient(135deg, var(--pp-primary), var(--pp-primary-2));
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px rgba(65, 84, 241, 0.28);
        }
        .btn-select i { transition: transform 0.25s ease; }
        .btn-select:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(65, 84, 241, 0.4);
            text-decoration: none;
        }
        .btn-select:hover i { transform: translateX(3px); }
        .price-card.featured .btn-select {
            background: #ffffff;
            color: var(--pp-primary);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
        }
        .price-card.featured .btn-select:hover {
            background: var(--pp-accent);
            color: #1a1a1a;
        }

        /* Guarantee Row */
        .pricing-guarantee {
            margin-top: 3.5rem;
            padding: 2rem 1.5rem;
            background: #ffffff;
            border: 1px solid var(--pp-border);
            border-radius: 18px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .guarantee-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .guarantee-item .g-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--pp-primary), var(--pp-primary-2));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .guarantee-item h5 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--pp-text);
            margin: 0 0 2px;
        }
        .guarantee-item p {
            font-size: 0.82rem;
            color: var(--pp-muted);
            margin: 0;
            line-height: 1.4;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .price-card.featured { transform: none; }
            .price-card.featured:hover { transform: translateY(-6px); }
            .pricing-title { font-size: 2.1rem; }
        }
        @media (max-width: 767px) {
            .pricing-page { padding: 50px 0 60px; }
            .pricing-title { font-size: 1.75rem; }
            .plan-price .amount { font-size: 2rem; }
            .price-card { padding: 1.75rem 1.5rem; }
        }
        @media (max-width: 575px) {
            .category-nav .nav-link { padding: 0.5rem 1rem; font-size: 0.82rem; }
        }
    </style>
@endpush

@push('footer_js')
    <script>
        $(document).ready(function () {
            $('.category-nav a[data-toggle="pill"]').on('shown.bs.tab', function () {
                $('html, body').animate({
                    scrollTop: $('.tab-content').offset().top - 80
                }, 400);
            });
        });
    </script>
@endpush
