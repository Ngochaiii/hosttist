@extends('layouts.web.default')

@push('header_css')
    <link href="{{ asset('assets/web/hostit/css/homepage.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <section class="home-section">
        <div class="container">
            <div class="home-section-head">
                <span class="section-badge">{{ $category->name }}</span>
                <h2>Bảng giá {{ $category->name }}</h2>
                @if ($category->description)
                    <p>{{ Str::limit(trim(html_entity_decode(strip_tags($category->description), ENT_QUOTES, 'UTF-8')), 180) }}</p>
                @endif
            </div>

            @php
                // Gom các mức thông số có thật trong danh mục để dựng bộ lọc —
                // ô lọc nào không có dữ liệu thì không hiển thị
                $specOf = fn ($p) => $p->meta_data['specs'] ?? [];
                $cpuOptions = $products->map(fn ($p) => $specOf($p)['cpu'] ?? null)->filter()->unique()->sort()->values();
                $ramOptions = $products->map(fn ($p) => $specOf($p)['ram'] ?? null)->filter()->unique()->sort()->values();
                $priceRanges = [
                    '0-500000'        => 'Dưới 500.000₫',
                    '500000-1000000'  => '500.000₫ – 1 triệu',
                    '1000000-3000000' => '1 – 3 triệu',
                    '3000000-'        => 'Trên 3 triệu',
                ];
            @endphp

            @if ($products->count() > 1)
                <div class="filter-bar" id="filterBar">
                    <div class="filter-field filter-field--grow">
                        <label for="filterSearch"><i class="fas fa-search"></i> Tìm kiếm</label>
                        <input type="text" id="filterSearch" data-filter placeholder="Tên gói, ví dụ: C3, Xeon...">
                    </div>

                    @if ($cpuOptions->count() > 1)
                        <div class="filter-field">
                            <label for="filterCpu"><i class="fas fa-microchip"></i> CPU tối thiểu</label>
                            <select id="filterCpu" data-filter>
                                <option value="">Tất cả</option>
                                @foreach ($cpuOptions as $cpu)
                                    <option value="{{ $cpu }}">≥ {{ $cpu }} core</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($ramOptions->count() > 1)
                        <div class="filter-field">
                            <label for="filterRam"><i class="fas fa-memory"></i> RAM tối thiểu</label>
                            <select id="filterRam" data-filter>
                                <option value="">Tất cả</option>
                                @foreach ($ramOptions as $ram)
                                    <option value="{{ $ram }}">≥ {{ $ram }} GB</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="filter-field">
                        <label for="filterPrice"><i class="fas fa-coins"></i> Khoảng giá</label>
                        <select id="filterPrice" data-filter>
                            <option value="">Tất cả</option>
                            @foreach ($priceRanges as $range => $label)
                                <option value="{{ $range }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-field">
                        <label for="filterSort"><i class="fas fa-sort-amount-down"></i> Sắp xếp</label>
                        <select id="filterSort">
                            <option value="default">Mặc định</option>
                            <option value="price-asc">Giá thấp → cao</option>
                            <option value="price-desc">Giá cao → thấp</option>
                            <option value="name">Tên A → Z</option>
                        </select>
                    </div>

                    <div class="filter-meta">
                        <span id="filterCount">{{ $products->count() }}/{{ $products->count() }} gói</span>
                        <button type="button" id="filterReset" class="filter-reset" hidden>
                            <i class="fas fa-times"></i> Xóa lọc
                        </button>
                    </div>
                </div>
            @endif

            <div class="pricing-grid" id="productGrid">
                @forelse ($products as $product)
                    @php
                        $specs = $specOf($product);
                        $finalPrice = $product->sale_price ?: $product->price;
                        $hasSale = $product->sale_price && $product->price > $product->sale_price;
                    @endphp
                    <div class="pricing-card {{ $product->is_featured ? 'featured' : '' }} product-item"
                        data-name="{{ Str::lower($product->name) }}"
                        data-price="{{ (int) $finalPrice }}"
                        data-cpu="{{ $specs['cpu'] ?? '' }}"
                        data-ram="{{ $specs['ram'] ?? '' }}"
                        data-order="{{ $loop->index }}">
                        @if ($product->is_featured)
                            <div class="popular-badge">
                                <i class="fas fa-star"></i> Phổ biến nhất
                            </div>
                        @endif

                        <h3 class="plan-name">{{ $product->name }}</h3>

                        @if (!empty($specs))
                            <div class="spec-chips">
                                @isset($specs['cpu'])
                                    <span class="spec-chip"><i class="fas fa-microchip"></i> {{ $specs['cpu'] }} vCPU</span>
                                @endisset
                                @isset($specs['ram'])
                                    <span class="spec-chip"><i class="fas fa-memory"></i> {{ $specs['ram'] }}GB RAM</span>
                                @endisset
                                @isset($specs['storage'])
                                    <span class="spec-chip"><i class="fas fa-hdd"></i> {{ $specs['storage'] }}GB NVMe</span>
                                @endisset
                            </div>
                        @endif

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
                                    <span class="save-pill">-{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%</span>
                                </div>
                            @endif
                        </div>

                        <ul class="features-list">
                            @foreach ($product->featureList() as $feature)
                                <li class="feature-item">
                                    <div class="feature-icon"><i class="fas fa-check"></i></div>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="plan-actions">
                            <a href="{{ route('service.detail', $product->slug) }}" class="plan-btn plan-btn--buy">
                                <i class="fas fa-shopping-cart"></i> Đặt mua ngay
                            </a>
                            <a href="{{ route('service.detail', $product->slug) }}" class="plan-btn plan-btn--detail">
                                <i class="fas fa-info-circle"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="filter-empty">
                        <i class="fas fa-box-open"></i>
                        <h4>Không tìm thấy sản phẩm</h4>
                        <p>Hiện không có sản phẩm nào trong danh mục này. Vui lòng quay lại sau.</p>
                        <a href="{{ route('homepage') }}" class="plan-btn plan-btn--buy">Quay lại trang chủ</a>
                    </div>
                @endforelse
            </div>

            <div class="filter-empty" id="filterEmpty" hidden>
                <i class="fas fa-filter"></i>
                <h4>Không có gói nào khớp bộ lọc</h4>
                <p>Thử nới lỏng tiêu chí hoặc xóa bộ lọc để xem tất cả các gói.</p>
                <button type="button" class="plan-btn plan-btn--detail" onclick="document.getElementById('filterReset').click()">
                    <i class="fas fa-times"></i> Xóa bộ lọc
                </button>
            </div>
        </div>
    </section>
@endsection

@push('footer_js')
    <script>
        (function () {
            var bar = document.getElementById('filterBar');
            if (!bar) return;

            var grid = document.getElementById('productGrid');
            var cards = Array.prototype.slice.call(grid.querySelectorAll('.product-item'));
            var empty = document.getElementById('filterEmpty');
            var count = document.getElementById('filterCount');
            var reset = document.getElementById('filterReset');
            var search = document.getElementById('filterSearch');
            var cpu = document.getElementById('filterCpu');
            var ram = document.getElementById('filterRam');
            var price = document.getElementById('filterPrice');
            var sort = document.getElementById('filterSort');

            function matches(card) {
                if (search && search.value.trim()) {
                    var q = search.value.trim().toLowerCase();
                    if (card.dataset.name.indexOf(q) === -1) return false;
                }
                if (cpu && cpu.value) {
                    if (!card.dataset.cpu || parseInt(card.dataset.cpu) < parseInt(cpu.value)) return false;
                }
                if (ram && ram.value) {
                    if (!card.dataset.ram || parseInt(card.dataset.ram) < parseInt(ram.value)) return false;
                }
                if (price && price.value) {
                    var parts = price.value.split('-');
                    var p = parseInt(card.dataset.price) || 0;
                    if (parts[0] && p < parseInt(parts[0])) return false;
                    if (parts[1] && p >= parseInt(parts[1])) return false;
                }
                return true;
            }

            function apply() {
                var visible = 0;
                cards.forEach(function (card) {
                    var ok = matches(card);
                    card.hidden = !ok;
                    if (ok) visible++;
                });

                // Sắp xếp bằng cách đổi thứ tự node trong grid
                var sorted = cards.slice().sort(function (a, b) {
                    switch (sort.value) {
                        case 'price-asc':  return a.dataset.price - b.dataset.price;
                        case 'price-desc': return b.dataset.price - a.dataset.price;
                        case 'name':       return a.dataset.name.localeCompare(b.dataset.name);
                        default:           return a.dataset.order - b.dataset.order;
                    }
                });
                sorted.forEach(function (card) { grid.appendChild(card); });

                count.textContent = visible + '/' + cards.length + ' gói';
                empty.hidden = visible > 0;
                grid.hidden = visible === 0;

                var filtering = (search && search.value.trim()) || (cpu && cpu.value) ||
                    (ram && ram.value) || (price && price.value) || sort.value !== 'default';
                reset.hidden = !filtering;
            }

            bar.addEventListener('input', apply);
            bar.addEventListener('change', apply);
            reset.addEventListener('click', function () {
                if (search) search.value = '';
                if (cpu) cpu.value = '';
                if (ram) ram.value = '';
                if (price) price.value = '';
                sort.value = 'default';
                apply();
            });
        })();
    </script>
@endpush
