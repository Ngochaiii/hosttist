@extends('layouts.admin.index')

@section('content')
    <section class="content">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="row">
                {{-- Form thêm/sửa --}}
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">{{ $editing ? 'Sửa đuôi .' . $editing->tld : 'Thêm đuôi tên miền' }}</h3>
                        </div>
                        <form method="POST"
                            action="{{ $editing ? route('admin.domain-tlds.update', $editing->id) : route('admin.domain-tlds.store') }}">
                            @csrf
                            @if ($editing)
                                @method('PUT')
                            @endif
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Đuôi (không gồm dấu chấm) <span class="text-danger">*</span></label>
                                    <input type="text" name="tld" class="form-control" placeholder="com, net, vn, com.vn"
                                        value="{{ old('tld', $editing->tld ?? '') }}" required>
                                </div>

                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="is_vn" name="is_vn" value="1"
                                        {{ old('is_vn', $editing->is_vn ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_vn">Đuôi Việt Nam (.vn — cần giấy tờ chủ thể)</label>
                                </div>

                                <div class="row">
                                    <div class="col-6 form-group">
                                        <label>Giá gốc đăng ký (đ) <span class="text-danger">*</span></label>
                                        <input type="number" step="1000" min="0" name="register_cost" id="register_cost"
                                            class="form-control" value="{{ old('register_cost', $editing->register_cost ?? '') }}" required>
                                    </div>
                                    <div class="col-6 form-group">
                                        <label>Giá gốc gia hạn (đ) <span class="text-danger">*</span></label>
                                        <input type="number" step="1000" min="0" name="renew_cost"
                                            class="form-control" value="{{ old('renew_cost', $editing->renew_cost ?? '') }}" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Giá gốc transfer (đ) — tuỳ chọn</label>
                                    <input type="number" step="1000" min="0" name="transfer_cost"
                                        class="form-control" value="{{ old('transfer_cost', $editing->transfer_cost ?? '') }}">
                                </div>

                                <div class="row">
                                    <div class="col-6 form-group">
                                        <label>Kiểu markup <span class="text-danger">*</span></label>
                                        <select name="markup_type" id="markup_type" class="form-control">
                                            <option value="amount" {{ old('markup_type', $editing->markup_type ?? 'amount') === 'amount' ? 'selected' : '' }}>Số tiền (đ)</option>
                                            <option value="percent" {{ old('markup_type', $editing->markup_type ?? '') === 'percent' ? 'selected' : '' }}>Phần trăm (%)</option>
                                        </select>
                                    </div>
                                    <div class="col-6 form-group">
                                        <label>Markup <span class="text-danger">*</span></label>
                                        <input type="number" step="any" min="0" name="markup_value" id="markup_value"
                                            class="form-control" value="{{ old('markup_value', $editing->markup_value ?? 70000) }}" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Làm tròn giá bán tới (đ) — tuỳ chọn</label>
                                    <input type="number" step="1000" min="0" name="round_to" id="round_to"
                                        class="form-control" placeholder="vd 1000" value="{{ old('round_to', $editing->round_to ?? '') }}">
                                </div>

                                {{-- Xem trước giá bán & lãi --}}
                                <div class="callout callout-info py-2" id="preview">
                                    <strong>Giá bán dự kiến:</strong> <span id="prev_price">—</span>
                                    &nbsp;|&nbsp; <strong>Lãi:</strong> <span id="prev_profit">—</span>
                                </div>

                                <div class="row">
                                    <div class="col-4 form-group">
                                        <label>Năm tối thiểu</label>
                                        <input type="number" min="1" max="10" name="min_years" class="form-control"
                                            value="{{ old('min_years', $editing->min_years ?? 1) }}" required>
                                    </div>
                                    <div class="col-4 form-group">
                                        <label>Năm tối đa</label>
                                        <input type="number" min="1" max="10" name="max_years" class="form-control"
                                            value="{{ old('max_years', $editing->max_years ?? 10) }}" required>
                                    </div>
                                    <div class="col-4 form-group">
                                        <label>Thứ tự</label>
                                        <input type="number" name="sort_order" class="form-control"
                                            value="{{ old('sort_order', $editing->sort_order ?? 0) }}">
                                    </div>
                                </div>

                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                        {{ old('is_active', $editing->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Đang bán</label>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">{{ $editing ? 'Cập nhật' : 'Thêm' }}</button>
                                @if ($editing)
                                    <a href="{{ route('admin.domain-tlds.index') }}" class="btn btn-default">Huỷ</a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Bảng danh sách --}}
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Danh mục đuôi tên miền</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Đuôi</th>
                                        <th class="text-right">Giá gốc ĐK</th>
                                        <th class="text-right">Giá bán ĐK</th>
                                        <th class="text-right">Lãi</th>
                                        <th class="text-right">Giá bán GH</th>
                                        <th>Markup</th>
                                        <th>Trạng thái</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($tlds as $tld)
                                        <tr>
                                            <td>
                                                <strong>.{{ $tld->tld }}</strong>
                                                @if ($tld->is_vn)<span class="badge badge-warning">VN</span>@endif
                                            </td>
                                            <td class="text-right">{{ number_format($tld->register_cost) }}</td>
                                            <td class="text-right text-primary"><strong>{{ number_format($tld->register_price) }}</strong></td>
                                            <td class="text-right text-success">+{{ number_format($tld->register_profit) }}</td>
                                            <td class="text-right">{{ number_format($tld->renew_price) }}</td>
                                            <td>{{ $tld->markup_type === 'percent' ? rtrim(rtrim(number_format($tld->markup_value, 2), '0'), '.') . '%' : number_format($tld->markup_value) . 'đ' }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.domain-tlds.toggleStatus', $tld->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm {{ $tld->is_active ? 'btn-success' : 'btn-secondary' }}">
                                                        {{ $tld->is_active ? 'Đang bán' : 'Tắt' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.domain-tlds.index', ['edit' => $tld->id]) }}" class="btn btn-sm btn-info">Sửa</a>
                                                <form method="POST" action="{{ route('admin.domain-tlds.destroy', $tld->id) }}" class="d-inline"
                                                    onsubmit="return confirm('Xoá đuôi .{{ $tld->tld }}?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-danger">Xoá</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có đuôi nào. Thêm đuôi đầu tiên bên trái.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('js')
<script>
    (function () {
        const cost = document.getElementById('register_cost');
        const type = document.getElementById('markup_type');
        const val = document.getElementById('markup_value');
        const round = document.getElementById('round_to');
        const pPrice = document.getElementById('prev_price');
        const pProfit = document.getElementById('prev_profit');

        function fmt(n) { return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ'; }
        function calc() {
            const c = parseFloat(cost.value) || 0;
            const m = parseFloat(val.value) || 0;
            let price = type.value === 'percent' ? c * (1 + m / 100) : c + m;
            const r = parseInt(round.value) || 0;
            if (r > 0) price = Math.round(price / r) * r;
            pPrice.textContent = fmt(price);
            pProfit.textContent = fmt(price - c);
        }
        [cost, type, val, round].forEach(el => el && el.addEventListener('input', calc));
        calc();
    })();
</script>
@endpush
