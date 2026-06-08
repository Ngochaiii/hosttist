@extends('layouts.admin.index')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="mb-3">
                <a href="{{ route('admin.domains.index') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ $domain->domain_name }}</h3></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th style="width:35%">Trạng thái</th><td>{{ $domain->status }}</td></tr>
                                <tr><th>Khách hàng</th><td>{{ $domain->customer->user->name ?? '— (kho)' }}</td></tr>
                                <tr><th>Đuôi</th><td>.{{ $domain->tld }} @if ($domain->tldRef && $domain->tldRef->is_vn)<span class="badge badge-warning">VN</span>@endif</td></tr>
                                <tr><th>Số năm</th><td>{{ $domain->years }}</td></tr>
                                <tr><th>Ngày đăng ký</th><td>{{ $domain->registered_at?->format('d/m/Y') ?? '—' }}</td></tr>
                                <tr><th>Ngày hết hạn</th><td>{{ $domain->expires_at?->format('d/m/Y') ?? '—' }}</td></tr>
                                <tr><th>Nhà đăng ký</th><td>{{ $domain->registrar }}</td></tr>
                                <tr><th>Auto-renew</th><td>{{ $domain->auto_renew ? 'Có' : 'Không' }}</td></tr>
                                <tr><th>Nameservers</th><td>{!! $domain->nameservers ? implode('<br>', $domain->nameservers) : '—' !!}</td></tr>
                                <tr><th>Auth code</th><td><code>{{ $domain->auth_code ?? '—' }}</code></td></tr>
                                <tr><th>Ghi chú</th><td>{{ $domain->notes ?? '—' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card card-success">
                        <div class="card-header"><h3 class="card-title">Tài chính</h3></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th>Giá gốc</th><td class="text-right">{{ number_format($domain->cost_price) }}đ</td></tr>
                                <tr><th>Giá bán</th><td class="text-right">{{ number_format($domain->sell_price) }}đ</td></tr>
                                <tr class="table-success"><th>Lãi</th><td class="text-right"><strong>+{{ number_format($domain->profit) }}đ</strong></td></tr>
                                <tr><th>Nguồn</th><td class="text-right">{{ $domain->source === 'admin_import' ? 'Import' : 'Đơn hàng' }}</td></tr>
                            </table>
                        </div>
                    </div>

                    @if ($domain->registrant)
                        <div class="card card-info">
                            <div class="card-header"><h3 class="card-title">Chủ thể (đã giải mã)</h3></div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr><th>Tên</th><td>{{ $domain->registrant['name'] ?? '—' }}</td></tr>
                                    <tr><th>Email</th><td>{{ $domain->registrant['email'] ?? '—' }}</td></tr>
                                    <tr><th>SĐT</th><td>{{ $domain->registrant['phone'] ?? '—' }}</td></tr>
                                    <tr><th>Địa chỉ</th><td>{{ $domain->registrant['address'] ?? '—' }}</td></tr>
                                    <tr><th>Giấy tờ</th><td>{{ $domain->registrant['id_type'] ?? '—' }} {{ $domain->registrant['id_number'] ?? '' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
