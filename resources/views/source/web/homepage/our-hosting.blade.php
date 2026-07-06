{{-- Gói dịch vụ nổi bật — style trong assets/web/hostit/css/homepage.css --}}
@if ($featuredProducts->isNotEmpty())
    <section class="home-section home-section--alt">
        <div class="container">
            <div class="home-section-head">
                <span class="section-badge">Bảng giá</span>
                <h2>Gói dịch vụ nổi bật</h2>
                <p>
                    Chọn gói phù hợp với nhu cầu của bạn. Tất cả gói đều bao gồm SSL miễn phí,
                    backup tự động và hỗ trợ kỹ thuật 24/7.
                </p>
            </div>

            <div class="pricing-grid">
                @foreach ($featuredProducts as $index => $product)
                    @php
                        $finalPrice = $product->sale_price ?: $product->price;
                        $hasSale = $product->sale_price && $product->price > $product->sale_price;
                        $savePct = $hasSale ? round((($product->price - $product->sale_price) / $product->price) * 100) : 0;
                    @endphp
                    <div class="pricing-card {{ $index == 1 ? 'featured' : '' }}">
                        @if ($index == 1)
                            <div class="popular-badge">
                                <i class="fas fa-star"></i> Phổ biến nhất
                            </div>
                        @endif

                        <h3 class="plan-name">{{ $product->name }}</h3>
                        <p class="plan-description">
                            {{ Str::limit(trim(strip_tags($product->short_description)) ?: 'Giải pháp phù hợp cho nhu cầu của bạn', 110) }}
                        </p>

                        <div class="price-block">
                            <div class="price-row">
                                <span class="price">{{ number_format($finalPrice, 0, ',', '.') }}</span>
                                <span class="currency">₫</span>
                                <span class="period">
                                    @if ($product->is_recurring)
                                        /{{ $product->recurring_period == 12 ? 'năm' : ($product->recurring_period == 1 ? 'tháng' : $product->recurring_period . ' tháng') }}
                                    @else
                                        /trọn gói
                                    @endif
                                </span>
                            </div>
                            @if ($hasSale)
                                <div class="original-price">
                                    <del>{{ number_format($product->price, 0, ',', '.') }}₫</del>
                                    <span class="save-pill">-{{ $savePct }}%</span>
                                </div>
                            @endif
                        </div>

                        <ul class="features-list">
                            @forelse ($product->featureList() as $feature)
                                <li class="feature-item">
                                    <div class="feature-icon"><i class="fas fa-check"></i></div>
                                    <span>{{ $feature }}</span>
                                </li>
                            @empty
                                <li class="feature-item">
                                    <div class="feature-icon"><i class="fas fa-check"></i></div>
                                    <span>SSL miễn phí, backup tự động, hỗ trợ 24/7</span>
                                </li>
                            @endforelse
                        </ul>

                        @if ($product->stock !== -1 && $product->stock <= 5 && $product->stock > 0)
                            <div class="stock-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Chỉ còn {{ $product->stock }} suất
                            </div>
                        @endif

                        <div class="plan-actions">
                            <a href="{{ route('service.detail', $product->slug) }}" class="plan-btn plan-btn--buy">
                                <i class="fas fa-shopping-cart"></i> Đặt mua ngay
                            </a>
                            <a href="{{ route('service.detail', $product->slug) }}" class="plan-btn plan-btn--detail">
                                <i class="fas fa-info-circle"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
