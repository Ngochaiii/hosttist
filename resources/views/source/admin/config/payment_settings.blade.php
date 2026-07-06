@extends('layouts.admin.index')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Cấu hình thanh toán</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active">Cấu hình thanh toán</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form action="{{ route('admin.configs.updatePayment') }}" method="POST" enctype="multipart/form-data">
                @csrf

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <!-- Thông tin công ty (in trên báo giá) -->
                    <div class="col-md-12">
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Thông tin công ty (hiển thị trên báo giá PDF)</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="company_name">Tên công ty</label>
                                            <input type="text" class="form-control" id="company_name"
                                                name="company_name"
                                                value="{{ old('company_name', $config->company_name) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="company_tax_code">Mã số thuế</label>
                                            <input type="text" class="form-control" id="company_tax_code"
                                                name="company_tax_code"
                                                value="{{ old('company_tax_code', $config->company_tax_code) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="company_address">Địa chỉ</label>
                                            <input type="text" class="form-control" id="company_address"
                                                name="company_address"
                                                value="{{ old('company_address', $config->company_address) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="company_phone">Điện thoại</label>
                                            <input type="text" class="form-control" id="company_phone"
                                                name="company_phone"
                                                value="{{ old('company_phone', $config->company_phone) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="company_email">Email</label>
                                            <input type="text" class="form-control" id="company_email"
                                                name="company_email"
                                                value="{{ old('company_email', $config->company_email) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ngân hàng -->
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Tài khoản ngân hàng (KHÔNG xuất hóa đơn VAT)</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="company_bank_name">Tên ngân hàng</label>
                                    <input type="text" class="form-control" id="company_bank_name"
                                        name="company_bank_name"
                                        value="{{ old('company_bank_name', $config->company_bank_name) }}">
                                </div>

                                <div class="form-group">
                                    <label for="company_bank_account_number">Số tài khoản</label>
                                    <input type="text" class="form-control" id="company_bank_account_number"
                                        name="company_bank_account_number"
                                        value="{{ old('company_bank_account_number', $config->company_bank_account_number) }}">
                                </div>

                                <div class="form-group">
                                    <label for="company_bank_account_name">Tên chủ tài khoản</label>
                                    <input type="text" class="form-control" id="company_bank_account_name"
                                        name="company_bank_account_name"
                                        value="{{ old('company_bank_account_name', $config->company_bank_account_name) }}">
                                </div>

                                <div class="form-group">
                                    <label for="company_bank_branch">Chi nhánh</label>
                                    <input type="text" class="form-control" id="company_bank_branch"
                                        name="company_bank_branch"
                                        value="{{ old('company_bank_branch', $config->company_bank_branch) }}">
                                </div>

                                <div class="form-group">
                                    <label for="company_bank_qr_code">Mã QR thanh toán</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="company_bank_qr_code"
                                                name="company_bank_qr_code" accept="image/*">
                                            <label class="custom-file-label" for="company_bank_qr_code">Chọn file</label>
                                        </div>
                                    </div>
                                    @if (Config::get('company_bank_qr_code'))
                                        <div class="mt-2">
                                            <img src="{{ Storage::url(Config::get('company_bank_qr_code')) }}"
                                                alt="QR Code Ngân hàng" class="img-thumbnail" style="max-height: 150px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Ngân hàng (xuất hóa đơn VAT) -->
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Tài khoản ngân hàng (XUẤT hóa đơn VAT)</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="vat_bank_name">Tên ngân hàng</label>
                                    <input type="text" class="form-control" id="vat_bank_name"
                                        name="vat_bank_name"
                                        value="{{ old('vat_bank_name', $config->vat_bank_name) }}">
                                </div>

                                <div class="form-group">
                                    <label for="vat_bank_account_number">Số tài khoản</label>
                                    <input type="text" class="form-control" id="vat_bank_account_number"
                                        name="vat_bank_account_number"
                                        value="{{ old('vat_bank_account_number', $config->vat_bank_account_number) }}">
                                </div>

                                <div class="form-group">
                                    <label for="vat_bank_account_name">Tên chủ tài khoản</label>
                                    <input type="text" class="form-control" id="vat_bank_account_name"
                                        name="vat_bank_account_name"
                                        value="{{ old('vat_bank_account_name', $config->vat_bank_account_name) }}">
                                </div>

                                <div class="form-group">
                                    <label for="vat_bank_branch">Chi nhánh</label>
                                    <input type="text" class="form-control" id="vat_bank_branch"
                                        name="vat_bank_branch"
                                        value="{{ old('vat_bank_branch', $config->vat_bank_branch) }}">
                                </div>

                                <div class="form-group">
                                    <label for="vat_bank_qr_code">Mã QR thanh toán</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="vat_bank_qr_code"
                                                name="vat_bank_qr_code" accept="image/*">
                                            <label class="custom-file-label" for="vat_bank_qr_code">Chọn file</label>
                                        </div>
                                    </div>
                                    @if (Config::get('vat_bank_qr_code'))
                                        <div class="mt-2">
                                            <img src="{{ Storage::url(Config::get('vat_bank_qr_code')) }}"
                                                alt="QR Code Ngân hàng VAT" class="img-thumbnail" style="max-height: 150px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Momo và ZaloPay -->
                    <div class="col-md-6">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Thông tin ví điện tử</h3>
                            </div>
                            <div class="card-body">
                                <!-- MoMo -->
                                <div class="mb-4">
                                    <h5>Ví MoMo</h5>
                                    <div class="form-group">
                                        <label for="momo_phone_number">Số điện thoại MoMo</label>
                                        <input type="text" class="form-control" id="momo_phone_number"
                                            name="momo_phone_number"
                                            value="{{ old('momo_phone_number', $config->momo_phone_number) }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="momo_account_name">Tên tài khoản MoMo</label>
                                        <input type="text" class="form-control" id="momo_account_name"
                                            name="momo_account_name"
                                            value="{{ old('momo_account_name', $config->momo_account_name) }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="momo_qr_code">Mã QR MoMo</label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="momo_qr_code"
                                                    name="momo_qr_code" accept="image/*">
                                                <label class="custom-file-label" for="momo_qr_code">Chọn file</label>
                                            </div>
                                        </div>
                                        @if ($config->momo_qr_code)
                                            <div class="mt-2">
                                                <img src="{{ Storage::url($config->momo_qr_code) }}" alt="QR Code MoMo"
                                                    class="img-thumbnail" style="max-height: 150px;">
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- ZaloPay -->
                                <div>
                                    <h5>Ví ZaloPay</h5>
                                    <div class="form-group">
                                        <label for="zalopay_phone_number">Số điện thoại ZaloPay</label>
                                        <input type="text" class="form-control" id="zalopay_phone_number"
                                            name="zalopay_phone_number"
                                            value="{{ old('zalopay_phone_number', $config->zalopay_phone_number) }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="zalopay_account_name">Tên tài khoản ZaloPay</label>
                                        <input type="text" class="form-control" id="zalopay_account_name"
                                            name="zalopay_account_name"
                                            value="{{ old('zalopay_account_name', $config->zalopay_account_name) }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="zalopay_qr_code">Mã QR ZaloPay</label>
                                        <div class="input-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="zalopay_qr_code"
                                                    name="zalopay_qr_code" accept="image/*">
                                                <label class="custom-file-label" for="zalopay_qr_code">Chọn file</label>
                                            </div>
                                        </div>
                                        @if ($config->zalopay_qr_code)
                                            <div class="mt-2">
                                                <img src="{{ Storage::url($config->zalopay_qr_code) }}"
                                                    alt="QR Code ZaloPay" class="img-thumbnail"
                                                    style="max-height: 150px;">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Cài đặt nạp tiền -->
                    <div class="col-md-12">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">Cấu hình nạp tiền</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="deposit_note_format">Định dạng nội dung chuyển khoản</label>
                                            <input type="text" class="form-control" id="deposit_note_format"
                                                name="deposit_note_format"
                                                value="{{ old('deposit_note_format', $config->deposit_note_format) }}"
                                                placeholder="Ví dụ: NapTien {customer_id}">
                                            <small class="form-text text-muted">Sử dụng {customer_id} để thay thế ID khách
                                                hàng</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="min_deposit_amount">Số tiền nạp tối thiểu</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="min_deposit_amount"
                                                    name="min_deposit_amount"
                                                    value="{{ old('min_deposit_amount', $config->min_deposit_amount) }}"
                                                    min="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">VNĐ</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="max_deposit_amount">Số tiền nạp tối đa</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="max_deposit_amount"
                                                    name="max_deposit_amount"
                                                    value="{{ old('max_deposit_amount', $config->max_deposit_amount) }}"
                                                    min="0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">VNĐ</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="deposit_instruction">Hướng dẫn nạp tiền</label>
                                            <textarea class="form-control" id="deposit_instruction" name="deposit_instruction" rows="8">{{ old('deposit_instruction', $config->deposit_instruction) }}</textarea>
                                            <small class="form-text text-muted">Hướng dẫn sẽ hiển thị cho khách hàng khi họ
                                                nạp tiền</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">Lưu cấu hình</button>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-lg">Quay lại Dashboard</a>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('js')
    <script>
        $(function() {
            // bs-custom-file-input
            bsCustomFileInput.init();

            // CKEditor for rich text instruction
            if (typeof ClassicEditor !== 'undefined') {
                ClassicEditor
                    .create(document.querySelector('#deposit_instruction'))
                    .catch(error => {
                        console.error(error);
                    });
            }
        });
    </script>
@endpush
