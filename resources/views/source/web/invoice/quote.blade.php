@extends('layouts.web.default')

@section('content')
    <section class="invoice-section py-5">
        <div class="container">
            <!-- Thanh tiến trình -->
            <div class="progress-container mb-4">
                <div class="row">
                    <div class="col-md-4 text-center progress-step active">
                        <div class="step-circle">1</div>
                        <div class="step-label">Cart</div>
                    </div>
                    <div class="col-md-4 text-center progress-step active">
                        <div class="step-circle">2</div>
                        <div class="step-label">Quote</div>
                    </div>
                    <div class="col-md-4 text-center progress-step">
                        <div class="step-circle">3</div>
                        <div class="step-label">Payment</div>
                    </div>
                </div>
            </div>

            <!-- Thông báo -->
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if (session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            <!-- Báo giá -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $cart->items[0]->product->type == 'ssl' ? 'SSL Certificate' : 'Hosting/Domain' }}
                        Invoice #{{ $quoteNumber }}</h4>
                    <span class="badge badge-danger">Unpaid</span>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Payment to:</h5>
                            <address>
                                <strong>{{ $config->company_name ?? 'Hostist company' }}</strong><br>
                                {{ $config->company_address ?? '5335 Gate Pkwy, 2nd Floor, Jacksonville, FL 32256' }}<br>
                                <strong>Email Services:</strong>
                                {{ $config->support_phone ?? 'supposthostit@gmail.com' }}<br>
                                <strong>Email:</strong> {{ $config->support_email ?? 'supposthostit@gmail.com' }}<br>
                                <strong>Complaint / Feedback:</strong>
                                {{ $config->complaint_phone ?? 'supposthostit@gmail.com' }}<br>
                            </address>
                        </div>

                        <div class="col-md-6 text-right">
                            <h5>Invoice to:</h5>
                            <address>
                                <strong>{{ $user->name }}</strong><br>
                                {{ $user->address ?? 'Address not provided' }}<br>
                                <strong>Email:</strong> {{ $user->email }}<br>
                                @if ($user->customer && $user->customer->website)
                                    <strong>Domain:</strong> {{ $user->customer->website }}<br>
                                @endif
                                <strong>Invoice Date:</strong> {{ $quoteDate }}<br>
                                <strong>Due Date:</strong> {{ $expireDate }}
                            </address>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cart->items as $item)
                                    @php
                                        $options = json_decode($item->options, true) ?: [];
                                        $period = $options['period'] ?? 1;
                                        $productName = $item->product->name;
                                        $domainName =
                                            $user->customer && $user->customer->website
                                                ? $user->customer->website
                                                : 'your-domain.com';
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $period }} Year {{ $productName }}
                                            @if ($item->product->type == 'ssl')
                                                (Discounted)
                                                for {{ $domainName }}
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }} đ</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Subtotal</th>
                                    <th class="text-right">{{ number_format($subtotal, 0, ',', '.') }} đ</th>
                                </tr>
                                <tr>
                                    <th>{{ (int) ($vatRate * 100) }}% VAT</th>
                                    <th class="text-right">{{ number_format($vatAmount, 0, ',', '.') }} đ</th>
                                </tr>
                                <tr>
                                    <th>Credit</th>
                                    <th class="text-right">0 đ</th>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">{{ number_format($total, 0, ',', '.') }} đ</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Package Includes</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        @if ($cart->items[0]->product->type == 'ssl')
                                            <li><i class="fa fa-check text-success mr-2"></i> Automatic Activation</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Immediate Issuance (with DV
                                                certificates)</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Unlimited Reissuance</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Website Identity Verification
                                            </li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Encrypted Data Transmission
                                            </li>
                                            <li><i class="fa fa-check text-success mr-2"></i> HTTPS Website Security</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Improved Search Rankings</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Builds Customer Trust</li>
                                        @else
                                            <li><i class="fa fa-check text-success mr-2"></i> 24/7 Technical Support</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> 99.9% Uptime Guarantee</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Free SSL Certificate</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Easy Control Panel</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Daily Backups</li>
                                            <li><i class="fa fa-check text-success mr-2"></i> Unlimited Bandwidth</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Total Amount Due</h5>
                                </div>
                                <div class="card-body">
                                    <h3 class="text-center mb-4">{{ number_format($total, 0, ',', '.') }} đ</h3>

                                    @php $bank = $config ? $config->bankInfo($vatInvoice ?? false) : []; @endphp
                                    <h5>Payment Method:</h5>
                                    <div class="payment-info">
                                        <p><strong>Bank Transfer (QR)</strong></p>
                                        <p><strong>Bank:</strong> {{ $bank['name'] ?? 'ACB' }}</p>
                                        <p><strong>Account Number:</strong>
                                            {{ $bank['account_number'] ?? '24768' }}</p>
                                        <p><strong>Amount:</strong> {{ number_format($total, 0) }} VND</p>
                                        <p><strong>Payment Reference:</strong>
                                            {{ str_replace('QUOTE-', 'HD', $quoteNumber) }}</p>

                                        <!-- QR code placeholder -->
                                        @if (!empty($bank['qr_code']))
                                            <div class="text-center mt-3">
                                                <img src="{{ asset('storage/' . $bank['qr_code']) }}"
                                                    alt="QR Code" style="max-width: 150px;" class="img-fluid">
                                            </div>
                                        @else
                                            <div class="text-center mt-3">
                                                <img src="{{ asset('images/qr-placeholder.png') }}" alt="QR Code"
                                                    style="max-width: 150px;" class="img-fluid">
                                            </div>
                                        @endif

                                        <p class="text-center mt-3">
                                            <small>After payment, your certificate will be automatically activated within
                                                1-5 minutes after {{ $config->company_name ?? 'AZDIGI' }} receives your
                                                payment.</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- VAT / Xuất hoá đơn công ty --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="vatInvoiceToggle"
                               {{ !empty($vatInvoice) ? 'checked' : '' }}>
                        <label class="form-check-label" for="vatInvoiceToggle">
                            <strong>Xuất hoá đơn công ty (VAT 10%)</strong>
                        </label>
                        <div class="small text-muted">
                            Tick vào ô này nếu bạn cần xuất hoá đơn VAT cho công ty. Tổng cộng sẽ cộng thêm 10% VAT.
                        </div>
                    </div>

                    <div id="vatCompanyFields" class="mt-3" style="{{ !empty($vatInvoice) ? '' : 'display:none;' }}">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Tên công ty <span class="text-danger">*</span></label>
                                <input type="text" name="vat_company_name" form="paymentForm"
                                       class="form-control" maxlength="255"
                                       value="{{ old('vat_company_name') }}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Mã số thuế <span class="text-danger">*</span></label>
                                <input type="text" name="vat_tax_code" form="paymentForm"
                                       class="form-control" maxlength="50"
                                       placeholder="0123456789"
                                       value="{{ old('vat_tax_code') }}">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Địa chỉ công ty <span class="text-danger">*</span></label>
                                <input type="text" name="vat_company_address" form="paymentForm"
                                       class="form-control" maxlength="255"
                                       value="{{ old('vat_company_address') }}">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Email nhận hoá đơn <span class="text-danger">*</span></label>
                                <input type="email" name="vat_company_email" form="paymentForm"
                                       class="form-control" maxlength="255"
                                       value="{{ old('vat_company_email', $user->email) }}">
                            </div>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger mt-2 mb-0">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Nút tải PDF và gửi email -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <a href="{{ route('quote.download') }}" id="downloadPdfBtn"
                       class="btn btn-outline-primary btn-block">
                        <i class="fa fa-download mr-2"></i> Tải PDF báo giá
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="{{ route('quote.email') }}" id="emailQuoteBtn"
                       class="btn btn-outline-info btn-block">
                        <i class="fa fa-envelope mr-2"></i> Gửi báo giá qua email
                    </a>
                </div>

                <div class="col-md-4">
                    <form action="{{ route('process.payment') }}" method="post" id="paymentForm">
                        @csrf
                        <input type="hidden" name="vat_invoice" id="vatInvoiceHidden" value="{{ !empty($vatInvoice) ? '1' : '0' }}">
                        @php
                            $hasWalletBalance = $user->customer && $user->customer->hasBalance($total);
                            $walletBalance = $user->customer ? $user->customer->balance : 0;
                        @endphp
                        <div class="mb-2 text-left">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pmWallet" value="wallet"
                                       {{ $hasWalletBalance ? 'checked' : 'disabled' }}>
                                <label class="form-check-label" for="pmWallet">
                                    Ví của tôi ({{ number_format($walletBalance, 0, ',', '.') }} đ)
                                    @if (!$hasWalletBalance)
                                        <small class="text-danger">– không đủ số dư</small>
                                    @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pmBank" value="bank"
                                       {{ !$hasWalletBalance ? 'checked' : '' }}>
                                <label class="form-check-label" for="pmBank">
                                    Chuyển khoản ngân hàng
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-credit-card mr-2"></i> Tiếp tục thanh toán
                        </button>
                    </form>
                </div>
            </div>

            <script>
                (function () {
                    const toggle = document.getElementById('vatInvoiceToggle');
                    const fields = document.getElementById('vatCompanyFields');
                    const hidden = document.getElementById('vatInvoiceHidden');
                    const dlBtn  = document.getElementById('downloadPdfBtn');
                    const emBtn  = document.getElementById('emailQuoteBtn');
                    const quoteUrl = @json(route('quote'));

                    function apply() {
                        const on = toggle.checked;
                        fields.style.display = on ? '' : 'none';
                        hidden.value = on ? '1' : '0';
                        const qs = on ? '?vat_invoice=1' : '';
                        dlBtn.href = @json(route('quote.download')) + qs;
                        emBtn.href = @json(route('quote.email'))    + qs;
                    }
                    toggle.addEventListener('change', function () {
                        apply();
                        // Reload trang quote để cập nhật tổng tiền/VAT
                        window.location.href = quoteUrl + (toggle.checked ? '?vat_invoice=1' : '');
                    });
                    apply();
                })();
            </script>
            <!-- Nút quay lại -->
            <div class="text-center">
                <a href="{{ route('cart.index') }}" class="btn btn-link">
                    <i class="fa fa-arrow-left mr-2"></i> Quay lại giỏ hàng
                </a>
            </div>
        </div>
    </section>
@endsection

@push('header_css')
    <style>
        /* Thanh tiến trình */
        .progress-container {
            margin: 30px 0;
        }

        .progress-step {
            position: relative;
        }

        .progress-step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 2px;
            background-color: #dee2e6;
            z-index: 1;
        }

        .progress-step.active:not(:last-child):after {
            background-color: #28a745;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
            z-index: 2;
        }

        .progress-step.active .step-circle {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .step-label {
            font-weight: 500;
        }

        .progress-step.active .step-label {
            color: #28a745;
        }

        /* Báo giá */
        .invoice-section {
            background-color: #f8f9fa;
        }

        .payment-info p {
            margin-bottom: 5px;
        }
    </style>
@endpush
