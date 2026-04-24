<!-- CSS cho Modern Pricing Section -->
<style>
    .pricing-section {
        position: relative;
        background: linear-gradient(180deg, #f7f9ff 0%, #ffffff 100%);
        padding: 80px 0;
        overflow: hidden;
    }

    .pricing-section::before {
        content: '';
        position: absolute;
        top: -120px;
        right: -120px;
        width: 340px;
        height: 340px;
        background: radial-gradient(circle, rgba(65, 84, 241, 0.12) 0%, transparent 70%);
        pointer-events: none;
    }

    .pricing-section::after {
        content: '';
        position: absolute;
        bottom: -100px;
        left: -100px;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(118, 75, 162, 0.1) 0%, transparent 70%);
        pointer-events: none;
    }

    .pricing-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
        position: relative;
        z-index: 1;
    }

    .section-header {
        text-align: center;
        margin-bottom: 3.5rem;
    }

    .section-header .section-badge {
        display: inline-block;
        background: rgba(65, 84, 241, 0.1);
        color: #4154f1;
        padding: 6px 18px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .section-title {
        font-size: 2.6rem;
        font-weight: 800;
        color: #1a1a1a;
        margin-bottom: 1rem;
        letter-spacing: -0.5px;
    }

    .section-subtitle {
        font-size: 1.05rem;
        color: #6b7280;
        max-width: 620px;
        margin: 0 auto;
        line-height: 1.65;
    }

    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.75rem;
        margin-bottom: 3rem;
        align-items: stretch;
    }

    .pricing-card {
        background: #ffffff;
        border: 1px solid #e9ecf5;
        border-radius: 20px;
        padding: 2.25rem 2rem;
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
        display: flex;
        flex-direction: column;
    }

    .pricing-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 22px 44px rgba(65, 84, 241, 0.14);
        border-color: #c7cdf0;
    }

    .pricing-card.featured {
        background: linear-gradient(160deg, #4154f1 0%, #667eea 55%, #764ba2 100%);
        color: #ffffff;
        border: none;
        box-shadow: 0 22px 44px rgba(65, 84, 241, 0.32);
        transform: translateY(-10px);
    }

    .pricing-card.featured:hover {
        transform: translateY(-14px);
        box-shadow: 0 28px 55px rgba(65, 84, 241, 0.42);
    }

    .popular-badge {
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

    .plan-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1.2rem;
        background: rgba(65, 84, 241, 0.1);
        color: #4154f1;
    }

    .pricing-card.featured .plan-icon {
        background: rgba(255, 255, 255, 0.2);
        color: #ffd700;
    }

    .plan-name {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 0.4rem;
    }

    .pricing-card.featured .plan-name {
        color: #ffffff;
    }

    .plan-description {
        color: #6b7280;
        font-size: 0.92rem;
        margin-bottom: 1.4rem;
        min-height: 42px;
    }

    .pricing-card.featured .plan-description {
        color: rgba(255, 255, 255, 0.85);
    }

    .price-block {
        margin-bottom: 1.75rem;
        padding-bottom: 1.4rem;
        border-bottom: 1px dashed #e5e7f0;
    }

    .pricing-card.featured .price-block {
        border-bottom-color: rgba(255, 255, 255, 0.25);
    }

    .price-row {
        display: flex;
        align-items: baseline;
        gap: 4px;
        flex-wrap: wrap;
    }

    .price {
        font-size: 2.6rem;
        font-weight: 800;
        color: #1a1a1a;
        line-height: 1;
        letter-spacing: -1px;
    }

    .pricing-card.featured .price {
        color: #ffffff;
    }

    .currency {
        font-size: 1.1rem;
        font-weight: 700;
        color: #4154f1;
    }

    .pricing-card.featured .currency {
        color: #ffd700;
    }

    .period {
        color: #9ca3af;
        font-size: 0.9rem;
        font-weight: 500;
        margin-left: 4px;
    }

    .pricing-card.featured .period {
        color: rgba(255, 255, 255, 0.75);
    }

    .original-price {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
        font-size: 0.9rem;
    }

    .original-price del {
        color: #9ca3af;
    }

    .pricing-card.featured .original-price del {
        color: rgba(255, 255, 255, 0.6);
    }

    .save-pill {
        background: #fee2e2;
        color: #dc2626;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .pricing-card.featured .save-pill {
        background: rgba(255, 215, 0, 0.2);
        color: #ffd700;
    }

    .features-list {
        list-style: none;
        padding: 0;
        margin: 0 0 1.75rem;
        flex: 1;
    }

    .feature-item {
        display: flex;
        align-items: flex-start;
        padding: 0.55rem 0;
        color: #374151;
        font-size: 0.93rem;
        line-height: 1.5;
    }

    .pricing-card.featured .feature-item {
        color: rgba(255, 255, 255, 0.92);
    }

    .feature-icon {
        width: 20px;
        height: 20px;
        background: rgba(16, 185, 129, 0.14);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .feature-icon i {
        font-size: 0.65rem;
        color: #10b981;
    }

    .pricing-card.featured .feature-icon {
        background: rgba(255, 215, 0, 0.2);
    }

    .pricing-card.featured .feature-icon i {
        color: #ffd700;
    }

    .stock-warning {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        padding: 0.55rem 0.9rem;
        text-align: center;
        margin-bottom: 1rem;
        font-size: 0.82rem;
        color: #9a3412;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .pricing-card.featured .stock-warning {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
        color: #fff;
    }

    .cta-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .btn {
        padding: 0.85rem 1.25rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        text-align: center;
        transition: all 0.25s ease;
        border: 1.5px solid transparent;
        cursor: pointer;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4154f1, #667eea);
        color: #ffffff;
        box-shadow: 0 8px 20px rgba(65, 84, 241, 0.28);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 26px rgba(65, 84, 241, 0.4);
        color: #ffffff;
    }

    .pricing-card.featured .btn-primary {
        background: #ffffff;
        color: #4154f1;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
    }

    .pricing-card.featured .btn-primary:hover {
        background: #ffd700;
        color: #1a1a1a;
    }

    .btn-secondary {
        background: transparent;
        color: #4154f1;
        border-color: #d6dbf5;
    }

    .btn-secondary:hover {
        background: #f1f3ff;
        border-color: #4154f1;
        color: #4154f1;
    }

    .pricing-card.featured .btn-secondary {
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.5);
    }

    .pricing-card.featured .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: #ffffff;
        color: #ffffff;
    }

    .comparison-note {
        text-align: center;
        margin-top: 3rem;
        padding: 2rem 2.5rem;
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e9ecf5;
        box-shadow: 0 6px 22px rgba(15, 23, 42, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .comparison-note .cn-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, #4154f1, #667eea);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .comparison-note .cn-text {
        text-align: left;
        flex: 1;
        min-width: 240px;
    }

    .comparison-note h3 {
        color: #1a1a1a;
        font-size: 1.15rem;
        margin-bottom: 0.35rem;
        font-weight: 700;
    }

    .comparison-note p {
        color: #6b7280;
        line-height: 1.6;
        margin: 0;
        font-size: 0.92rem;
    }

    @media (max-width: 991px) {
        .pricing-card.featured { transform: none; }
        .pricing-card.featured:hover { transform: translateY(-6px); }
    }

    @media (max-width: 768px) {
        .pricing-section { padding: 60px 0; }
        .pricing-grid { grid-template-columns: 1fr; gap: 1.5rem; }
        .section-title { font-size: 2rem; }
        .price { font-size: 2.2rem; }
        .comparison-note { flex-direction: column; text-align: center; }
        .comparison-note .cn-text { text-align: center; }
    }

    /* Animation cho loading */
    .pricing-card {
        animation: slideUp 0.6s ease-out forwards;
    }

    .pricing-card:nth-child(2) { animation-delay: 0.1s; }
    .pricing-card:nth-child(3) { animation-delay: 0.2s; }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- HTML cho Modern Pricing Section -->
<section class="pricing-section">
    <div class="pricing-container">
        <div class="section-header">
            <span class="section-badge">Bảng giá</span>
            <h2 class="section-title">Hosting Solutions</h2>
            <p class="section-subtitle">
                Choose the perfect hosting plan for your needs. All plans include free SSL, automatic backups, and 24/7 support.
            </p>
        </div>

        @php
            $planIcons = ['fas fa-leaf', 'fas fa-rocket', 'fas fa-crown'];
            $planIconColors = ['#10b981', '#4154f1', '#8b5cf6'];
        @endphp

        <div class="pricing-grid">
            @forelse($featuredProducts as $index => $product)
                @php
                    $icon = $planIcons[$index % count($planIcons)];
                    $iconColor = $planIconColors[$index % count($planIconColors)];
                    $finalPrice = $product->sale_price ?: $product->price;
                    $hasSale = $product->sale_price && $product->price > $product->sale_price;
                    $savePct = $hasSale ? round((($product->price - $product->sale_price) / $product->price) * 100) : 0;
                @endphp
                <div class="pricing-card {{ $index == 1 ? 'featured' : '' }}">
                    @if($index == 1)
                        <div class="popular-badge">
                            <i class="fas fa-star"></i> Most Popular
                        </div>
                    @endif

                    <div class="plan-icon" @if($index != 1) style="background: {{ $iconColor }}1a; color: {{ $iconColor }};" @endif>
                        <i class="{{ $icon }}"></i>
                    </div>

                    <h3 class="plan-name">{{ $product->name }}</h3>
                    <p class="plan-description">
                        {{ $product->short_description ?? 'Perfect for your business needs' }}
                    </p>

                    <div class="price-block">
                        <div class="price-row">
                            <span class="price">{{ number_format($finalPrice, 0, ',', '.') }}</span>
                            <span class="currency">₫</span>
                            <span class="period">
                                @if($product->is_recurring)
                                    /{{ $product->recurring_period == 12 ? 'năm' : ($product->recurring_period == 1 ? 'tháng' : $product->recurring_period . ' tháng') }}
                                @else
                                    /trọn gói
                                @endif
                            </span>
                        </div>
                        @if($hasSale)
                            <div class="original-price">
                                <del>{{ number_format($product->price, 0, ',', '.') }}₫</del>
                                <span class="save-pill">-{{ $savePct }}%</span>
                            </div>
                        @endif
                    </div>

                    <ul class="features-list">
                        @php
                            $features = [];
                            if ($product->meta_data && isset($product->meta_data['features']) && is_array($product->meta_data['features'])) {
                                $features = $product->meta_data['features'];
                            }
                            if (empty($features) && $product->description) {
                                preg_match_all('/[\-\*]\s*([^\n\r]+)/', strip_tags($product->description), $matches);
                                if (!empty($matches[1])) {
                                    $features = array_slice($matches[1], 0, 6);
                                }
                            }
                            if (empty($features)) {
                                $features = [
                                    'High Performance SSD Storage',
                                    'Unlimited Bandwidth',
                                    'Free SSL Certificate',
                                    '24/7 Technical Support',
                                    'One-Click App Installation',
                                    'Weekly Automatic Backups'
                                ];
                            }
                        @endphp

                        @foreach(array_slice($features, 0, 6) as $feature)
                            <li class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if($product->stock !== -1 && $product->stock <= 5 && $product->stock > 0)
                        <div class="stock-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Chỉ còn {{ $product->stock }} suất
                        </div>
                    @endif

                    <div class="cta-buttons">
                        <a href="{{ route('service.detail', $product->slug) }}" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Đặt mua ngay
                        </a>
                        <a href="{{ route('service.detail', $product->slug) }}" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> Xem chi tiết
                        </a>
                    </div>
                </div>
            @empty
                @php
                    $fallback = [
                        ['name' => 'Basic Hosting',    'desc' => 'Phù hợp website cá nhân, blog nhỏ',  'price' => '199.000', 'period' => '/tháng', 'features' => ['5GB SSD Storage', 'Unlimited Bandwidth', '3 MySQL Databases', 'Free SSL Certificate']],
                        ['name' => 'Business Hosting', 'desc' => 'Tốt nhất cho doanh nghiệp SME',       'price' => '349.000', 'period' => '/tháng', 'features' => ['10GB SSD Storage', 'Unlimited Bandwidth', '10 MySQL Databases', 'Free SSL Certificate', 'Daily Backup', '24/7 Support']],
                        ['name' => 'Premium Hosting',  'desc' => 'Sức mạnh tối đa cho enterprise',      'price' => '899.000', 'period' => '/tháng', 'features' => ['30GB SSD Storage', 'Unlimited Bandwidth', 'Unlimited Databases', 'Free SSL Certificate']],
                    ];
                @endphp
                @foreach($fallback as $idx => $plan)
                    <div class="pricing-card {{ $idx == 1 ? 'featured' : '' }}">
                        @if($idx == 1)
                            <div class="popular-badge"><i class="fas fa-star"></i> Most Popular</div>
                        @endif
                        <div class="plan-icon" @if($idx != 1) style="background: {{ $planIconColors[$idx] }}1a; color: {{ $planIconColors[$idx] }};" @endif>
                            <i class="{{ $planIcons[$idx] }}"></i>
                        </div>
                        <h3 class="plan-name">{{ $plan['name'] }}</h3>
                        <p class="plan-description">{{ $plan['desc'] }}</p>
                        <div class="price-block">
                            <div class="price-row">
                                <span class="price">{{ $plan['price'] }}</span>
                                <span class="currency">₫</span>
                                <span class="period">{{ $plan['period'] }}</span>
                            </div>
                        </div>
                        <ul class="features-list">
                            @foreach($plan['features'] as $feature)
                                <li class="feature-item">
                                    <div class="feature-icon"><i class="fas fa-check"></i></div>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="cta-buttons">
                            <a href="{{ route('pricing.index') }}" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Đặt mua ngay
                            </a>
                            <a href="{{ route('pricing.index') }}" class="btn btn-secondary">
                                <i class="fas fa-info-circle"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>

        <div class="comparison-note">
            <div class="cn-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="cn-text">
                <h3>Cam kết chất lượng</h3>
                <p>
                    Tất cả gói hosting đều có uptime 99.9%, hỗ trợ kỹ thuật 24/7 và chính sách hoàn tiền trong 30 ngày.
                    Nâng cấp hoặc hạ gói bất cứ lúc nào không mất phí chuyển đổi.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript cho Interactive Effects -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.pricing-section .btn');
        buttons.forEach(button => {
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
            button.addEventListener('click', function (e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.35);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });
    });

    const rippleStyle = document.createElement('style');
    rippleStyle.textContent = `@keyframes ripple { to { transform: scale(4); opacity: 0; } }`;
    document.head.appendChild(rippleStyle);
</script>
