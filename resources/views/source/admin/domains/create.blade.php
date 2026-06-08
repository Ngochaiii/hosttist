@extends('layouts.admin.index')

@section('content')
    <section class="content">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif

            <form method="POST" action="{{ route('admin.domains.store') }}">
                @csrf
                <div class="row">
                    {{-- Thông tin tên miền --}}
                    <div class="col-md-7">
                        <div class="card card-primary">
                            <div class="card-header"><h3 class="card-title">Import tên miền đã mua (Nhân Hòa)</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Tên miền đầy đủ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="domain_name" id="domain_name" class="form-control"
                                            placeholder="vidu.com / vidu.com.vn" value="{{ old('domain_name') }}" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-primary" id="btn_check">Kiểm tra</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Đuôi được nhận diện tự động từ danh mục TLD.</small>
                                    <div id="check_result" class="mt-1 small"></div>
                                </div>

                                <div class="form-group">
                                    <label>Khách hàng</label>
                                    <select name="customer_id" class="form-control">
                                        <option value="">— Để kho (chưa gán khách) —</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->user->name ?? ('Customer #' . $c->id) }} {{ $c->user->email ? '(' . $c->user->email . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-4 form-group">
                                        <label>Giá gốc (đ) <span class="text-danger">*</span></label>
                                        <input type="number" step="1000" min="0" name="cost_price" id="cost_price"
                                            class="form-control" value="{{ old('cost_price') }}" required>
                                    </div>
                                    <div class="col-4 form-group">
                                        <label>Giá bán (đ)</label>
                                        <input type="number" step="1000" min="0" name="sell_price" id="sell_price"
                                            class="form-control" placeholder="Bỏ trống = tự tính theo markup đuôi"
                                            value="{{ old('sell_price') }}">
                                    </div>
                                    <div class="col-4 form-group">
                                        <label>Số năm</label>
                                        <input type="number" min="1" max="10" name="years" class="form-control"
                                            value="{{ old('years', 1) }}" required>
                                    </div>
                                </div>
                                <div class="callout callout-success py-2">
                                    <strong>Lãi:</strong> <span id="prev_profit">—</span>
                                    <small class="text-muted">(chỉ tính khi nhập cả giá bán)</small>
                                </div>

                                <div class="row">
                                    <div class="col-4 form-group">
                                        <label>Ngày đăng ký</label>
                                        <input type="date" name="registered_at" class="form-control" value="{{ old('registered_at') }}">
                                    </div>
                                    <div class="col-4 form-group">
                                        <label>Ngày hết hạn</label>
                                        <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                                    </div>
                                    <div class="col-4 form-group">
                                        <label>Trạng thái</label>
                                        <select name="status" class="form-control">
                                            @foreach (['active' => 'Hoạt động', 'pending' => 'Chờ', 'expired' => 'Hết hạn'] as $k => $v)
                                                <option value="{{ $k }}" {{ old('status', 'active') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Nameservers (mỗi dòng 1 NS)</label>
                                    <textarea name="nameservers" class="form-control" rows="2"
                                        placeholder="ns1.nhanhoa.com&#10;ns2.nhanhoa.com">{{ old('nameservers') }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-6 form-group">
                                        <label>Auth/EPP code</label>
                                        <input type="text" name="auth_code" class="form-control" value="{{ old('auth_code') }}">
                                    </div>
                                    <div class="col-6 form-group d-flex align-items-end">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="auto_renew" name="auto_renew" value="1"
                                                {{ old('auto_renew') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="auto_renew">Tự động gia hạn</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Ghi chú</label>
                                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Thông tin chủ thể --}}
                    <div class="col-md-5">
                        <div class="card card-info">
                            <div class="card-header"><h3 class="card-title">Thông tin chủ thể (registrant)</h3></div>
                            <div class="card-body">
                                <div class="alert alert-warning py-2 small">
                                    Với đuôi <strong>.vn</strong> cần CCCD (cá nhân) hoặc MST/giấy phép (doanh nghiệp). Thông tin được <strong>mã hóa</strong> khi lưu.
                                </div>
                                <div class="form-group">
                                    <label>Họ tên / Tên tổ chức</label>
                                    <input type="text" name="reg_name" class="form-control" value="{{ old('reg_name') }}">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="reg_email" class="form-control" value="{{ old('reg_email') }}">
                                </div>
                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="reg_phone" class="form-control" value="{{ old('reg_phone') }}">
                                </div>
                                <div class="form-group">
                                    <label>Địa chỉ</label>
                                    <input type="text" name="reg_address" class="form-control" value="{{ old('reg_address') }}">
                                </div>
                                <div class="row">
                                    <div class="col-5 form-group">
                                        <label>Loại giấy tờ</label>
                                        <select name="reg_id_type" class="form-control">
                                            <option value="">—</option>
                                            <option value="cccd" {{ old('reg_id_type') === 'cccd' ? 'selected' : '' }}>CCCD</option>
                                            <option value="passport" {{ old('reg_id_type') === 'passport' ? 'selected' : '' }}>Hộ chiếu</option>
                                            <option value="tax_code" {{ old('reg_id_type') === 'tax_code' ? 'selected' : '' }}>MST/Giấy phép</option>
                                        </select>
                                    </div>
                                    <div class="col-7 form-group">
                                        <label>Số giấy tờ</label>
                                        <input type="text" name="reg_id_number" class="form-control" value="{{ old('reg_id_number') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success">Import tên miền</button>
                                <a href="{{ route('admin.domains.index') }}" class="btn btn-default">Huỷ</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('js')
<script>
    (function () {
        const cost = document.getElementById('cost_price');
        const sell = document.getElementById('sell_price');
        const out = document.getElementById('prev_profit');
        function fmt(n) { return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ'; }
        function calc() {
            const c = parseFloat(cost.value) || 0;
            const s = parseFloat(sell.value);
            out.textContent = isNaN(s) ? '— (tự tính theo đuôi)' : fmt(s - c);
        }
        [cost, sell].forEach(el => el && el.addEventListener('input', calc));
        calc();
    })();

    // Kiểm tra khả dụng (RDAP)
    (function () {
        const btn = document.getElementById('btn_check');
        const input = document.getElementById('domain_name');
        const out = document.getElementById('check_result');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const domain = (input.value || '').trim();
            if (!domain) { out.innerHTML = '<span class="text-danger">Nhập tên miền trước.</span>'; return; }
            out.innerHTML = '<span class="text-muted">Đang kiểm tra...</span>';
            btn.disabled = true;

            fetch('{{ route('admin.domains.check') }}?domain=' + encodeURIComponent(domain), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(d => {
                const colors = { available: 'success', taken: 'danger', unknown: 'warning', invalid: 'danger' };
                out.innerHTML = '<span class="text-' + (colors[d.status] || 'muted') + '">' +
                    (d.status === 'available' ? '✅ ' : d.status === 'taken' ? '❌ ' : '⚠️ ') + d.message + '</span>';
            })
            .catch(() => { out.innerHTML = '<span class="text-danger">Lỗi kiểm tra.</span>'; })
            .finally(() => { btn.disabled = false; });
        });
    })();
</script>
@endpush
