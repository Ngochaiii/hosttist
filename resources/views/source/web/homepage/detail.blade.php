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
                                            <p><strong>Danh mục:</strong> {{ $product->category->name }}</p>
                                            @if ($product->category->getServiceLabel())
                                                <span
                                                    class="badge badge-info">{{ $product->category->getServiceLabel() }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="type mt-2">
                                        <p><strong>Loại:</strong>
                                            @switch($product->type)
                                                @case('ssl')
                                                    Chứng chỉ SSL
                                                @break

                                                @case('hosting')
                                                    Web Hosting
                                                @break

                                                @case('domain')
                                                    Tên miền
                                                @break

                                                @case('vps')
                                                    VPS/Cloud Server
                                                @break

                                                @default
                                                    {{ ucfirst($product->type) }}
                                            @endswitch
                                        </p>
                                    </div>

                                    @if ($product->is_recurring)
                                        <div class="billing_cycle mt-2">
                                            <p><strong>Chu kỳ thanh toán:</strong> {{ $product->recurring_period }} tháng
                                            </p>
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

                                        <!-- Bảng giá theo thời hạn -->
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
                                                                $priceByPeriod = [
                                                                    1 => $basePrice,
                                                                    2 => $basePrice * 2,
                                                                    3 => $basePrice * 3,
                                                                    5 => $basePrice * 5,
                                                                ];
                                                            @endphp

                                                            @foreach ($periods as $period)
                                                                <tr class="{{ $period == 1 ? 'table-active' : '' }}"
                                                                    id="period-row-{{ $period }}">
                                                                    <td>{{ $period }} năm</td>
                                                                    <td class="font-weight-bold">
                                                                        {{ number_format($priceByPeriod[$period], 0, ',', '.') }}
                                                                        đ
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="action_buttons mt-4">
                                            {{-- Thay thế phần form cũ bằng code này --}}
                                            <form action="{{ route('cart.add') }}" method="POST" id="add-to-cart-form">
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
                                                @endif

                                                {{-- DYNAMIC FIELDS TỪ CATEGORY META_DATA --}}
                                                @if ($product->category && $product->category->hasServiceFields())
                                                    <div class="service-fields-section bg-light p-3 rounded mb-3">
                                                        <h6 class="mb-3 text-primary">
                                                            <i class="fas fa-info-circle"></i> Thông tin bắt buộc cho dịch
                                                            vụ
                                                        </h6>

                                                        @foreach ($product->category->getServiceFields() as $field)
                                                            <div class="form-group mb-3">
                                                                <label for="{{ $field['name'] }}">
                                                                    {{ $field['label'] }}
                                                                    @if ($field['required'] ?? false)
                                                                        <span class="text-danger">*</span>
                                                                    @endif
                                                                </label>

                                                                @if ($field['type'] == 'select')
                                                                    <select name="options[{{ $field['name'] }}]"
                                                                        id="{{ $field['name'] }}" class="form-control"
                                                                        {{ $field['required'] ?? false ? 'required' : '' }}>
                                                                        @foreach ($field['options'] as $value => $label)
                                                                            <option value="{{ $value }}">
                                                                                {{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @elseif($field['type'] == 'textarea')
                                                                    <textarea name="options[{{ $field['name'] }}]" id="{{ $field['name'] }}" class="form-control" rows="3"
                                                                        placeholder="{{ $field['placeholder'] ?? '' }}" {{ $field['required'] ?? false ? 'required' : '' }}></textarea>
                                                                @elseif($field['type'] == 'checkbox')
                                                                    <div class="form-check">
                                                                        <input type="checkbox"
                                                                            name="options[{{ $field['name'] }}]"
                                                                            id="{{ $field['name'] }}"
                                                                            class="form-check-input" value="1"
                                                                            {{ $field['default'] ?? false ? 'checked' : '' }}>
                                                                        <label class="form-check-label"
                                                                            for="{{ $field['name'] }}">
                                                                            {{ $field['description'] ?? $field['label'] }}
                                                                        </label>
                                                                    </div>
                                                                @else
                                                                    <input type="{{ $field['type'] }}"
                                                                        name="options[{{ $field['name'] }}]"
                                                                        id="{{ $field['name'] }}" class="form-control"
                                                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                                                        @if (isset($field['min'])) min="{{ $field['min'] }}" @endif
                                                                        @if (isset($field['max'])) max="{{ $field['max'] }}" @endif
                                                                        @if (isset($field['validation'])) data-validation="{{ $field['validation'] }}" @endif
                                                                        {{ $field['required'] ?? false ? 'required' : '' }}>
                                                                @endif

                                                                @if (isset($field['description']) && $field['type'] != 'checkbox')
                                                                    <small
                                                                        class="form-text text-muted">{{ $field['description'] }}</small>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if ($product->is_recurring)
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="options[auto_renew]" id="auto_renew" value="1">
                                                        <label class="form-check-label" for="auto_renew">
                                                            Tự động gia hạn
                                                        </label>
                                                    </div>

                                                    <div class="price-display mb-3">
                                                        <p class="mb-1">Giá:
                                                            <span id="displayed-price"
                                                                class="font-weight-bold text-success">
                                                                {{ number_format($product->sale_price ?? $product->price, 0, ',', '.') }}
                                                                đ
                                                            </span>
                                                        </p>
                                                    </div>
                                                @endif

                                                <button type="submit" class="btn btn-primary btn-block mb-2">
                                                    <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                                                </button>
                                            </form>
                                            <a href="{{ route('contact.index') }}"
                                                class="btn btn-outline-primary btn-block">
                                                <i class="fas fa-phone"></i> Liên hệ tư vấn
                                            </a>
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
                            <h4 class="m-0">Mô tả chi tiết</h4>
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
                                <h4 class="m-0">Các gói dịch vụ</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Gói</th>
                                                <th>Mô tả</th>
                                                <th>Giá</th>
                                                <th>Thao tác</th>
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
                                                            class="btn btn-sm btn-info">Xem chi tiết</a>
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
                        <h3 class="mb-4">Dịch vụ liên quan</h3>
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
                                            class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
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
            const form = document.getElementById('add-to-cart-form');

            // Price calculation
            if (periodSelect && displayedPrice) {
                const basePrice = {{ $product->sale_price ?? $product->price }};
                const priceByPeriod = {
                    '1': basePrice,
                    '2': basePrice * 2,
                    '3': basePrice * 3,
                    '5': basePrice * 5
                };

                periodSelect.addEventListener('change', function() {
                    const period = parseInt(this.value);
                    const newPrice = priceByPeriod[period] || basePrice;
                    displayedPrice.textContent = new Intl.NumberFormat('vi-VN').format(newPrice) + ' đ';

                    // Highlight table row
                    document.querySelectorAll('[id^="period-row-"]').forEach(row => {
                        row.classList.remove('table-active');
                    });
                    const activeRow = document.getElementById('period-row-' + period);
                    if (activeRow) {
                        activeRow.classList.add('table-active');
                    }
                });

                periodSelect.dispatchEvent(new Event('change'));
            }

            // VALIDATION FOR DYNAMIC FIELDS

            // Domain validation
            document.querySelectorAll('[data-validation="domain"]').forEach(field => {
                field.addEventListener('blur', function() {
                    const value = this.value.trim().toLowerCase();
                    const domainRegex =
                        /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](\.[a-zA-Z]{2,})+$/;

                    this.value = value;
                    this.classList.remove('is-invalid', 'is-valid');

                    if (value) {
                        if (domainRegex.test(value)) {
                            this.classList.add('is-valid');
                            removeError(this);
                        } else {
                            this.classList.add('is-invalid');
                            showError(this, 'Vui lòng nhập tên miền hợp lệ (ví dụ: example.com)');
                        }
                    }
                });
            });

            // Phone validation (Vietnam)
            document.querySelectorAll('[data-validation="phone_vn"]').forEach(field => {
                field.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const phoneRegex = /^(0[3|5|7|8|9])+([0-9]{8})$/;

                    this.classList.remove('is-invalid', 'is-valid');

                    if (value) {
                        if (phoneRegex.test(value)) {
                            this.classList.add('is-valid');
                            removeError(this);
                        } else {
                            this.classList.add('is-invalid');
                            showError(this, 'Số điện thoại không hợp lệ (VD: 0901234567)');
                        }
                    }
                });
            });

            // URL validation
            document.querySelectorAll('[data-validation="url"], input[type="url"]').forEach(field => {
                field.addEventListener('blur', function() {
                    const value = this.value.trim();
                    this.classList.remove('is-invalid', 'is-valid');

                    if (value) {
                        try {
                            new URL(value);
                            this.classList.add('is-valid');
                            removeError(this);
                        } catch (e) {
                            this.classList.add('is-invalid');
                            showError(this,
                                'URL không hợp lệ (phải bắt đầu bằng http:// hoặc https://)');
                        }
                    }
                });
            });

            // Alphanumeric validation
            document.querySelectorAll('[data-validation="alphanumeric"]').forEach(field => {
                field.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const alphanumericRegex = /^[a-z0-9_-]{3,16}$/i;

                    this.classList.remove('is-invalid', 'is-valid');

                    if (value) {
                        if (alphanumericRegex.test(value)) {
                            this.classList.add('is-valid');
                            removeError(this);
                        } else {
                            this.classList.add('is-invalid');
                            showError(this,
                                'Chỉ chứa chữ cái, số, gạch dưới và gạch ngang (3-16 ký tự)');
                        }
                    }
                });
            });

            // Email validation
            document.querySelectorAll('input[type="email"]').forEach(field => {
                field.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    this.classList.remove('is-invalid', 'is-valid');

                    if (value) {
                        if (emailRegex.test(value)) {
                            this.classList.add('is-valid');
                            removeError(this);
                        } else {
                            this.classList.add('is-invalid');
                            showError(this, 'Email không hợp lệ');
                        }
                    }
                });
            });

            // Helper functions
            function showError(input, message) {
                removeError(input);
                const errorDiv = document.createElement('div');
                errorDiv.classList.add('invalid-feedback', 'd-block');
                errorDiv.textContent = message;
                input.parentNode.appendChild(errorDiv);
            }

            function removeError(input) {
                const existingError = input.parentNode.querySelector('.invalid-feedback');
                if (existingError) {
                    existingError.remove();
                }
            }

            // Form submission validation
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;

                    // Check all required fields
                    const requiredFields = form.querySelectorAll('[required]');
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            showError(field, 'Trường này là bắt buộc');
                            isValid = false;
                        }
                    });

                    // Check invalid fields
                    const invalidFields = form.querySelectorAll('.is-invalid');
                    if (invalidFields.length > 0) {
                        e.preventDefault();
                        invalidFields[0].focus();
                        alert('Vui lòng kiểm tra lại thông tin đã nhập');
                    }
                });
            }
        });
    </script>
@endpush

@push('header_css')
    <style>
        .service-fields-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

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

        .price-display {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        #displayed-price {
            font-size: 1.1rem;
        }

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

        .invalid-feedback.d-block {
            display: block !important;
        }
    </style>
@endpush
