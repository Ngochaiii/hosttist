@extends('layouts.web.default')

@section('content')
    @php
        $walletBalance   = $user->customer ? (float) $user->customer->balance : 0;
        $hasWalletEnough = $user->customer && $user->customer->hasBalance($total);
        $productName     = $service->product->name ?? ('Dịch vụ #' . $service->id);
        $cycleLabel      = match ($service->billing_cycle) {
            'monthly' => '1 tháng',
            'yearly'  => '1 năm',
            default   => '1 kỳ',
        };
    @endphp

    <section class="invoice-section py-5">
        <div class="container">

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fa fa-refresh mr-2"></i>Gia hạn dịch vụ</h4>
                    <span class="badge badge-warning">Chưa thanh toán</span>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Dịch vụ:</h5>
                            <address>
                                <strong>{{ $productName }}</strong><br>
                                Mã dịch vụ: #{{ $service->id }}<br>
                                Chu kỳ: {{ $cycleLabel }}<br>
                                @if ($service->expires_at)
                                    Hết hạn hiện tại:
                                    <strong class="text-danger">
                                        {{ \Carbon\Carbon::parse($service->expires_at)->format('d/m/Y') }}
                                    </strong>
                                @endif
                            </address>
                        </div>
                        <div class="col-md-6 text-md-right">
                            <h5>Khách hàng:</h5>
                            <address>
                                <strong>{{ $user->name }}</strong><br>
                                {{ $user->email }}<br>
                                @if ($user->customer)
                                    Số dư ví: <strong>{{ number_format($walletBalance, 0, ',', '.') }} đ</strong>
                                @endif
                            </address>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nội dung</th>
                                    <th class="text-center" style="width:100px;">SL</th>
                                    <th class="text-right" style="width:180px;">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>{{ $productName }}</strong><br>
                                        <small class="text-muted">Gia hạn thêm {{ $cycleLabel }}</small>
                                    </td>
                                    <td class="text-center">1</td>
                                    <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }} đ</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-right">Tạm tính:</td>
                                    <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }} đ</td>
                                </tr>
                                @if ($vatInvoice)
                                    <tr>
                                        <td colspan="2" class="text-right">VAT ({{ (int) ($vatRate * 100) }}%):</td>
                                        <td class="text-right">{{ number_format($vatAmount, 0, ',', '.') }} đ</td>
                                    </tr>
                                @endif
                                <tr class="bg-light">
                                    <td colspan="2" class="text-right"><strong>Tổng cộng:</strong></td>
                                    <td class="text-right">
                                        <strong class="text-danger">{{ number_format($total, 0, ',', '.') }} đ</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- VAT / Xuất hoá đơn công ty --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="vatInvoiceToggle"
                               {{ $vatInvoice ? 'checked' : '' }}>
                        <label class="form-check-label" for="vatInvoiceToggle">
                            <strong>Xuất hoá đơn công ty (VAT 10%)</strong>
                        </label>
                        <div class="small text-muted">
                            Tick nếu cần xuất hoá đơn VAT. Tổng cộng sẽ cộng thêm 10% VAT.
                        </div>
                    </div>

                    <div id="vatCompanyFields" class="mt-3" style="{{ $vatInvoice ? '' : 'display:none;' }}">
                        @if (!empty($lastVat))
                            <div class="alert alert-info py-2 mb-2">
                                <small>
                                    <i class="fa fa-check-circle"></i>
                                    Đã tự động điền từ hoá đơn VAT gần nhất của bạn. Kiểm tra lại và chỉnh sửa nếu cần.
                                </small>
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Tên công ty <span class="text-danger">*</span></label>
                                <input type="text" name="vat_company_name" form="renewForm"
                                       class="form-control" maxlength="255"
                                       value="{{ old('vat_company_name', $lastVat->vat_company_name ?? '') }}">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Mã số thuế <span class="text-danger">*</span></label>
                                <input type="text" name="vat_tax_code" form="renewForm"
                                       class="form-control" maxlength="50"
                                       placeholder="0123456789"
                                       value="{{ old('vat_tax_code', $lastVat->vat_tax_code ?? '') }}">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Địa chỉ công ty <span class="text-danger">*</span></label>
                                <input type="text" name="vat_company_address" form="renewForm"
                                       class="form-control" maxlength="255"
                                       value="{{ old('vat_company_address', $lastVat->vat_company_address ?? '') }}">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Email nhận hoá đơn <span class="text-danger">*</span></label>
                                <input type="email" name="vat_company_email" form="renewForm"
                                       class="form-control" maxlength="255"
                                       value="{{ old('vat_company_email', $lastVat->vat_company_email ?? $user->email) }}">
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

            {{-- Form thanh toán --}}
            <div class="row mb-4">
                <div class="col-md-6 offset-md-3">
                    <form action="{{ route('customer.services.service.renew', $service->id) }}"
                          method="post" id="renewForm">
                        @csrf
                        <input type="hidden" name="vat_invoice" id="vatInvoiceHidden"
                               value="{{ $vatInvoice ? '1' : '0' }}">

                        <div class="mb-2 text-left">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pmWallet" value="wallet"
                                       {{ $hasWalletEnough ? 'checked' : 'disabled' }}>
                                <label class="form-check-label" for="pmWallet">
                                    Ví của tôi ({{ number_format($walletBalance, 0, ',', '.') }} đ)
                                    @if (!$hasWalletEnough)
                                        <small class="text-danger">– không đủ số dư</small>
                                    @endif
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method"
                                       id="pmBank" value="bank"
                                       {{ !$hasWalletEnough ? 'checked' : '' }}>
                                <label class="form-check-label" for="pmBank">
                                    Chuyển khoản ngân hàng
                                </label>
                            </div>
                        </div>

                        <div class="form-check mb-3 text-left">
                            <input class="form-check-input" type="checkbox" name="auto_renew"
                                   id="autoRenewToggle" value="1"
                                   {{ old('auto_renew', $service->auto_renew) ? 'checked' : '' }}>
                            <label class="form-check-label" for="autoRenewToggle">
                                <i class="fa fa-refresh"></i> Bật tự động gia hạn cho chu kỳ tiếp theo
                            </label>
                            <small class="text-muted d-block">
                                Hệ thống sẽ tự trừ ví khi tới hạn. Bạn có thể tắt bất cứ lúc nào.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-credit-card mr-2"></i> Xác nhận gia hạn
                        </button>
                    </form>

                    <a href="{{ route('customer.services.index') }}?tab=services"
                       class="btn btn-outline-secondary btn-block mt-2">
                        <i class="fa fa-arrow-left mr-1"></i> Huỷ và quay lại
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            const toggle   = document.getElementById('vatInvoiceToggle');
            const fields   = document.getElementById('vatCompanyFields');
            const hidden   = document.getElementById('vatInvoiceHidden');
            const quoteUrl = @json(route('customer.services.service.renew.quote', $service->id));

            toggle.addEventListener('change', function () {
                fields.style.display = this.checked ? '' : 'none';
                hidden.value = this.checked ? '1' : '0';
                // Reload quote để tính lại tổng có/không VAT
                window.location.href = quoteUrl + (this.checked ? '?vat_invoice=1' : '');
            });
        })();
    </script>
@endsection
