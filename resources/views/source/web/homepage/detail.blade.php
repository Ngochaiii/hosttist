@extends('layouts.web.index')

@section('content')
    <section class="service_detail_section layout_padding">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>{{ $product->name }}</h2>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="service_info card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="short_desc">
                                        <p class="lead">
                                            {{ $product->short_description ?? Str::limit(strip_tags($product->description), 200) }}
                                        </p>
                                    </div>

                                    @if ($product->category)
                                        <div class="category mt-3">
                                            <p><strong>Category:</strong> {{ $product->category->name }}</p>
                                        </div>
                                    @endif

                                    <div class="type mt-2">
                                        <p><strong>Type:</strong>
                                            @switch($product->type)
                                                @case('ssl')
                                                    SSL Certificate
                                                @break

                                                @case('hosting')
                                                    Hosting
                                                @break

                                                @case('domain')
                                                    Domain Name
                                                @break

                                                @default
                                                    {{ ucfirst($product->type) }}
                                            @endswitch
                                        </p>
                                    </div>

                                    @if ($product->is_recurring)
                                        <div class="billing_cycle mt-2">
                                            <p><strong>Billing Cycle:</strong> {{ $product->recurring_period }} months</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-4">
                                    <div class="price_box text-center p-4 bg-light rounded">
                                        @if ($product->sale_price)
                                            <h3 class="sale_price text-success">
                                                {{ number_format($product->sale_price, 0, ',', '.') }} đ</h3>
                                            <h5 class="regular_price text-muted">
                                                <del>{{ number_format($product->price, 0, ',', '.') }} đ</del>
                                            </h5>
                                        @else
                                            <h3 class="text-primary">{{ number_format($product->price, 0, ',', '.') }} đ
                                            </h3>
                                        @endif

                                        <!-- Thay thế phần bảng giá trong trang chi tiết -->
                                        @if ($product->is_recurring)
                                            <div class="price-calculator mt-3 mb-3 text-left">
                                                <h5 class="border-bottom pb-2">Bảng giá theo thời hạn</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th>Thời hạn</th>
                                                                <th>Giá</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                $basePrice = $product->sale_price ?? $product->price;
                                                                $periods = [1, 2, 3, 5];
                                                                // Giả sử đây là giá đã được admin thiết lập cho từng thời hạn
                                                                $priceByPeriod = [
                                                                    1 => $basePrice, // Giá 1 năm
                                                                    2 => $basePrice * 2, // Giá 2 năm
                                                                    3 => $basePrice * 3, // Giá 3 năm
                                                                    5 => $basePrice * 5, // Giá 5 năm
                                                                ];
                                                            @endphp

                                                            @foreach ($periods as $period)
                                                                <tr class="{{ $period == 1 ? 'table-active' : '' }}"
                                                                    id="period-row-{{ $period }}">
                                                                    <td>{{ $period }} năm</td>
                                                                    <td class="font-weight-bold">
                                                                        {{ number_format($priceByPeriod[$period], 0, ',', '.') }}
                                                                        đ</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="action_buttons mt-4">
                                            <form action="{{ route('cart.add') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="quantity" value="1">

                                                @if ($product->is_recurring)
                                                    <div class="form-group mb-3">
                                                        <label for="period">Thời hạn:</label>
                                                        <select name="options[period]" id="period" class="form-control"
                                                            required>
                                                            <option value="1">1 năm</option>
                                                            <option value="2">2 năm</option>
                                                            <option value="3">3 năm</option>
                                                            <option value="5">5 năm</option>
                                                        </select>
                                                    </div>

                                                    <!-- THÊM TRƯỜNG DOMAIN CHO SSL VÀ DOMAIN -->
                                                    @if ($product->type == 'ssl' || $product->type == 'domain')
                                                        <div class="form-group mb-3">
                                                            <label for="domain">Domain:</label>
                                                            <input type="text" name="options[domain]" id="domain"
                                                                class="form-control @error('options.domain') is-invalid @enderror"
                                                                placeholder="example.com" required>
                                                            <small class="form-text text-muted">
                                                                @if ($product->type == 'ssl')
                                                                    Nhập tên miền để cài đặt chứng chỉ SSL
                                                                @elseif($product->type == 'domain')
                                                                    Nhập tên miền bạn muốn đăng ký/gia hạn
                                                                @endif
                                                            </small>
                                                            @error('options.domain')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    @endif
                                                    {{-- TRƯỜNG BẮT BUỘC THEO LOẠI DỊCH VỤ --}}

                                                    {{-- VPS: yêu cầu nhập username muốn tạo --}}
                                                    @if ($product->type == 'vps')
                                                        <div class="form-group mb-3">
                                                            <label for="vps_username">Username VPS (bắt buộc)</label>
                                                            <input type="text" name="options[username]" id="vps_username"
                                                                class="form-control" placeholder="vd: myuser" required>
                                                            <small class="form-text text-muted">Username admin cho máy chủ
                                                                VPS của bạn.</small>
                                                        </div>
                                                    @endif

                                                    {{-- Web thiết kế: yêu cầu số điện thoại liên hệ --}}
                                                    @if ($product->type == 'web_design' || $product->type == 'service')
                                                        <div class="form-group mb-3">
                                                            <label for="contact_phone">Số điện thoại liên hệ (bắt
                                                                buộc)</label>
                                                            <input type="tel" name="options[phone]" id="contact_phone"
                                                                class="form-control" placeholder="09xxxxxxxx" required>
                                                            <small class="form-text text-muted">Dùng để liên hệ xác nhận và
                                                                lấy nội dung thiết kế.</small>
                                                        </div>
                                                    @endif

                                                    {{-- Hosting (tuỳ chọn): domain chính nếu bạn muốn gom sẵn ở đây --}}
                                                    @if ($product->type == 'hosting')
                                                        <div class="form-group mb-3">
                                                            <label for="primary_domain">Domain chính (tuỳ chọn)</label>
                                                            <input type="text" name="options[domain]" id="primary_domain"
                                                                class="form-control" placeholder="example.com">
                                                            <small class="form-text text-muted">Nếu có sẵn tên miền, nhập để
                                                                gán làm primary domain.</small>
                                                        </div>
                                                    @endif


                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="options[auto_renew]" id="auto_renew" value="1">
                                                        <label class="form-check-label" for="auto_renew">
                                                            Tự động gia hạn
                                                        </label>
                                                    </div>

                                                    <div class="price-display mb-3">
                                                        <p class="mb-1">Giá: <span id="displayed-price"
                                                                class="font-weight-bold text-success">
                                                                {{ number_format($product->sale_price ?? $product->price, 0, ',', '.') }}
                                                                đ</span>
                                                        </p>
                                                        <p class="mb-0 savings-info" id="savings-info"
                                                            style="display: none;">
                                                            <small class="text-success">Tiết kiệm: <span
                                                                    id="savings-amount">0</span> đ
                                                                (<span id="savings-percent">0</span>%)</small>
                                                        </p>
                                                    </div>
                                                @endif

                                                <button type="submit" class="btn btn-primary btn-block mb-2">Add to
                                                    Cart</button>
                                            </form>
                                            <a href="{{ route('contact.index') }}"
                                                class="btn btn-outline-primary btn-block">Contact Us</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="m-0">Description</h4>
                        </div>
                        <div class="card-body">
                            <div class="description-content">
                                {!! $product->description !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($variants->count() > 0)
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="m-0">Available Plans</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Plan</th>
                                                <th>Description</th>
                                                <th>Price</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($variants as $variant)
                                                <tr>
                                                    <td><strong>{{ $variant->name }}</strong></td>
                                                    <td>{{ $variant->short_description ?? Str::limit(strip_tags($variant->description), 100) }}
                                                    </td>
                                                    <td class="text-right">
                                                        {{ number_format($variant->price, 0, ',', '.') }} đ</td>
                                                    <td>
                                                        <a href="{{ route('service.detail', $variant->slug) }}"
                                                            class="btn btn-sm btn-info">View Details</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($relatedProducts->count() > 0)
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="mb-4">Related Services</h3>
                    </div>

                    @foreach ($relatedProducts as $related)
                        <div class="col-md-6 col-lg-4">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h4>{{ $related->name }}</h4>
                                    <p>{{ Str::limit($related->short_description ?? strip_tags($related->description), 120) }}
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <h5 class="text-primary mb-0">{{ number_format($related->price, 0, ',', '.') }} đ
                                        </h5>
                                        <a href="{{ route('service.detail', $related->slug) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
@push('footer_js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodSelect = document.getElementById('period');
            const displayedPrice = document.getElementById('displayed-price');

            // Giá cơ bản cho 1 năm
            const basePrice = {{ $product->sale_price ?? $product->price }};

            // Giá theo thời hạn (đã thiết lập bởi admin)
            const priceByPeriod = {
                '1': basePrice, // Giá 1 năm
                '2': basePrice * 2, // Giá 2 năm
                '3': basePrice * 3, // Giá 3 năm
                '5': basePrice * 5 // Giá 5 năm
            };

            // Cập nhật giá khi thay đổi thời hạn
            periodSelect.addEventListener('change', function() {
                const period = parseInt(this.value);

                // Lấy giá theo thời hạn
                const newPrice = priceByPeriod[period] || basePrice;

                // Hiển thị giá
                displayedPrice.textContent = new Intl.NumberFormat('vi-VN').format(newPrice) + ' đ';

                // Highlight dòng tương ứng trong bảng giá
                document.querySelectorAll('[id^="period-row-"]').forEach(row => {
                    row.classList.remove('table-active');
                });
                const activeRow = document.getElementById('period-row-' + period);
                if (activeRow) {
                    activeRow.classList.add('table-active');
                }

                // Cập nhật hidden input để gửi giá mới khi submit form
                const priceInput = document.createElement('input');
                priceInput.type = 'hidden';
                priceInput.name = 'custom_price';
                priceInput.value = newPrice;

                // Xóa input cũ nếu có
                const oldPriceInput = document.querySelector('input[name="custom_price"]');
                if (oldPriceInput) {
                    oldPriceInput.remove();
                }

                // Thêm input mới
                document.querySelector('form').appendChild(priceInput);
            });

            // Kích hoạt sự kiện change để cập nhật giá ban đầu
            periodSelect.dispatchEvent(new Event('change'));

            // Thêm validation cho trường domain nếu có
            const domainInput = document.getElementById('domain');
            if (domainInput) {
                domainInput.addEventListener('blur', function() {
                    this.value = this.value.trim().toLowerCase();

                    // Simple domain validation
                    const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9](\.[a-zA-Z]{2,})+$/;
                    if (this.value && !domainRegex.test(this.value)) {
                        this.classList.add('is-invalid');
                        let feedback = this.nextElementSibling.nextElementSibling;
                        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                            feedback = document.createElement('div');
                            feedback.classList.add('invalid-feedback');
                            this.parentNode.appendChild(feedback);
                        }
                        feedback.textContent = 'Vui lòng nhập tên miền hợp lệ (ví dụ: example.com)';
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
            }
        });
    </script>
@endpush
@push('header_css')
    <style>
        .price-calculator {
            margin-top: 20px;
            border-radius: 5px;
            background-color: #f8f9fa;
            padding: 15px;
        }

        .price-calculator h5 {
            color: #007bff;
            margin-bottom: 15px;
        }

        .table-active {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }

        .savings-info {
            font-size: 0.85rem;
        }

        .price-display {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        #displayed-price {
            font-size: 1.1rem;
        }

        /* Thêm style cho form domain */
        .form-control.is-valid {
            border-color: #28a745;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
    </style>
@endpush
