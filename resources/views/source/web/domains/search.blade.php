@extends('layouts.web.index')

@section('content')
<style>
    .domain-wrap { max-width: 900px; margin: 40px auto; padding: 0 15px; }
    .domain-card { background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,.08); padding:24px; margin-bottom:24px; }
    .domain-row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
    .domain-row .fld { flex:1; min-width:140px; }
    .domain-wrap label { font-weight:600; display:block; margin-bottom:4px; font-size:14px; }
    .domain-wrap input, .domain-wrap select, .domain-wrap textarea { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:6px; }
    .btn-dm { background:#0d6efd; color:#fff; border:none; padding:10px 18px; border-radius:6px; cursor:pointer; font-weight:600; }
    .btn-dm.green { background:#198754; }
    .btn-dm:disabled { opacity:.6; }
    .dm-alert { padding:10px 14px; border-radius:6px; margin-bottom:16px; }
    .dm-alert.err { background:#f8d7da; color:#842029; }
    .dm-alert.ok { background:#d1e7dd; color:#0f5132; }
    .price-table { width:100%; border-collapse:collapse; }
    .price-table th, .price-table td { padding:8px 10px; border-bottom:1px solid #eee; text-align:left; }
    .price-table td.r { text-align:right; }
    .muted { color:#777; font-size:13px; }
    #dm_preview { font-weight:700; }
    .reg-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    @media(max-width:600px){ .reg-grid{ grid-template-columns:1fr; } }
</style>

<div class="domain-wrap">
    <h2 style="margin-bottom:20px;">Đăng ký tên miền</h2>

    @if (session('error'))   <div class="dm-alert err">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="dm-alert ok">{{ session('success') }}</div> @endif
    @if ($errors->any())     <div class="dm-alert err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div> @endif

    <form class="domain-card" method="POST" action="{{ route('domains.add') }}" id="domainForm">
        @csrf
        <input type="hidden" name="domain_name" id="domain_name">

        <div class="domain-row">
            <div class="fld" style="flex:2;">
                <label>Tên miền muốn đăng ký</label>
                <input type="text" id="sld" placeholder="vidu" value="{{ old('sld') }}" autocomplete="off">
            </div>
            <div class="fld">
                <label>Đuôi</label>
                <select id="tld_select" name="tld_id">
                    @foreach ($tlds as $tld)
                        <option value="{{ $tld->id }}" data-tld="{{ $tld->tld }}"
                            data-price="{{ (int) $tld->register_price }}" data-isvn="{{ $tld->is_vn ? 1 : 0 }}"
                            data-min="{{ $tld->min_years }}" data-max="{{ $tld->max_years }}">
                            .{{ $tld->tld }} — {{ number_format($tld->register_price) }}đ/năm
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="fld" style="flex:0 0 110px;">
                <label>Số năm</label>
                <select id="years" name="years">
                    @for ($y = 1; $y <= 10; $y++)
                        <option value="{{ $y }}">{{ $y }} năm</option>
                    @endfor
                </select>
            </div>
            <div class="fld" style="flex:0 0 auto;">
                <button type="button" class="btn-dm" id="btn_check">Kiểm tra</button>
            </div>
        </div>

        <div style="margin-top:12px;">
            <span class="muted">Tên miền:</span> <span id="dm_preview">—</span>
            &nbsp;•&nbsp; <span class="muted">Tạm tính:</span> <span id="dm_price">—</span>
            <div id="check_result" style="margin-top:6px;"></div>
        </div>

        <hr style="margin:18px 0;">

        <label style="margin-bottom:10px;">Thông tin chủ thể
            <span class="muted" id="vn_note" style="display:none;">— đuôi .vn cần Họ tên + CCCD/MST</span>
        </label>
        <div class="reg-grid">
            <div><label>Họ tên / Tổ chức</label><input type="text" name="reg_name" value="{{ old('reg_name') }}"></div>
            <div><label>Email</label><input type="email" name="reg_email" value="{{ old('reg_email') }}"></div>
            <div><label>Số điện thoại</label><input type="text" name="reg_phone" value="{{ old('reg_phone') }}"></div>
            <div><label>Địa chỉ</label><input type="text" name="reg_address" value="{{ old('reg_address') }}"></div>
            <div>
                <label>Loại giấy tờ</label>
                <select name="reg_id_type">
                    <option value="">—</option>
                    <option value="cccd">CCCD</option>
                    <option value="passport">Hộ chiếu</option>
                    <option value="tax_code">MST/Giấy phép</option>
                </select>
            </div>
            <div><label>Số giấy tờ</label><input type="text" name="reg_id_number" value="{{ old('reg_id_number') }}"></div>
        </div>

        <div style="margin-top:18px;">
            <label style="display:inline-flex;align-items:center;gap:8px;font-weight:400;">
                <input type="checkbox" name="dns_management" value="1" checked style="width:auto;"> Dùng DNS của chúng tôi
            </label>
        </div>

        <div style="margin-top:18px;">
            <button type="submit" class="btn-dm green">Thêm vào giỏ hàng</button>
        </div>
    </form>

    <div class="domain-card">
        <h4 style="margin-bottom:14px;">Bảng giá tên miền</h4>
        <table class="price-table">
            <thead><tr><th>Đuôi</th><th class="r">Đăng ký</th><th class="r">Gia hạn</th></tr></thead>
            <tbody>
                @forelse ($tlds as $tld)
                    <tr>
                        <td><strong>.{{ $tld->tld }}</strong> @if ($tld->is_vn)<span class="muted">(VN)</span>@endif</td>
                        <td class="r">{{ number_format($tld->register_price) }}đ</td>
                        <td class="r">{{ number_format($tld->renew_price) }}đ</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Chưa có bảng giá.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sld = document.getElementById('sld');
    const tldSel = document.getElementById('tld_select');
    const years = document.getElementById('years');
    const hidden = document.getElementById('domain_name');
    const preview = document.getElementById('dm_preview');
    const priceOut = document.getElementById('dm_price');
    const checkOut = document.getElementById('check_result');
    const vnNote = document.getElementById('vn_note');
    const btnCheck = document.getElementById('btn_check');
    const form = document.getElementById('domainForm');

    function opt() { return tldSel.options[tldSel.selectedIndex]; }
    function fmt(n) { return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ'; }
    function fullDomain() {
        const s = (sld.value || '').trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
        return s ? s + '.' + opt().dataset.tld : '';
    }
    function refresh() {
        const d = fullDomain();
        hidden.value = d;
        preview.textContent = d || '—';
        const price = parseInt(opt().dataset.price) || 0;
        const y = parseInt(years.value) || 1;
        priceOut.textContent = d ? fmt(price * y) : '—';
        vnNote.style.display = opt().dataset.isvn === '1' ? 'inline' : 'none';
        checkOut.innerHTML = '';
    }
    [sld, tldSel, years].forEach(el => { el.addEventListener('input', refresh); el.addEventListener('change', refresh); });
    refresh();

    btnCheck.addEventListener('click', function () {
        const d = fullDomain();
        if (!d) { checkOut.innerHTML = '<span style="color:#b00">Nhập tên miền trước.</span>'; return; }
        checkOut.innerHTML = '<span class="muted">Đang kiểm tra...</span>';
        btnCheck.disabled = true;
        fetch('{{ route('domains.check') }}?domain=' + encodeURIComponent(d), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(j => {
                const c = j.status === 'available' ? '#198754' : (j.status === 'taken' ? '#b00' : '#b8860b');
                const ic = j.status === 'available' ? '✅ ' : (j.status === 'taken' ? '❌ ' : '⚠️ ');
                checkOut.innerHTML = '<span style="color:' + c + ';font-weight:600;">' + ic + j.message + '</span>';
            })
            .catch(() => { checkOut.innerHTML = '<span style="color:#b00">Lỗi kiểm tra.</span>'; })
            .finally(() => { btnCheck.disabled = false; });
    });

    form.addEventListener('submit', function (e) {
        refresh();
        if (!hidden.value) { e.preventDefault(); checkOut.innerHTML = '<span style="color:#b00">Vui lòng nhập tên miền.</span>'; }
    });
});
</script>
@endsection
