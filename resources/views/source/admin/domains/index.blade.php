@extends('layouts.admin.index')

@section('content')
    <section class="content">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif

            {{-- Báo cáo nhanh --}}
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner"><h3>{{ number_format($stats['total']) }}</h3><p>Tổng tên miền</p></div>
                        <div class="icon"><i class="fas fa-globe"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner"><h3>{{ number_format($stats['active']) }}</h3><p>Đang hoạt động</p></div>
                        <div class="icon"><i class="fas fa-check"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner"><h3>{{ number_format($stats['profit_month']) }}đ</h3><p>Lãi tháng này</p></div>
                        <div class="icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner"><h3>{{ number_format($stats['profit_all']) }}đ</h3><p>Tổng lãi</p></div>
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Kho tên miền</h3>
                    <a href="{{ route('admin.domains.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Import tên miền
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-inline mb-3">
                        <input type="text" name="q" class="form-control mr-2" placeholder="Tìm tên miền..."
                            value="{{ request('q') }}">
                        <select name="status" class="form-control mr-2">
                            <option value="">-- Trạng thái --</option>
                            @foreach (['pending' => 'Chờ', 'active' => 'Hoạt động', 'expired' => 'Hết hạn', 'cancelled' => 'Huỷ', 'transferred' => 'Đã chuyển'] as $k => $v)
                                <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary">Lọc</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Tên miền</th>
                                    <th>Khách hàng</th>
                                    <th class="text-right">Giá gốc</th>
                                    <th class="text-right">Giá bán</th>
                                    <th class="text-right">Lãi</th>
                                    <th>Hết hạn</th>
                                    <th>Trạng thái</th>
                                    <th>Nguồn</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($domains as $d)
                                    <tr>
                                        <td><strong>{{ $d->domain_name }}</strong></td>
                                        <td>{{ $d->customer->user->name ?? ($d->customer_id ? '#' . $d->customer_id : '— (kho)') }}</td>
                                        <td class="text-right">{{ number_format($d->cost_price) }}</td>
                                        <td class="text-right">{{ number_format($d->sell_price) }}</td>
                                        <td class="text-right text-success">+{{ number_format($d->profit) }}</td>
                                        <td>{{ $d->expires_at ? $d->expires_at->format('d/m/Y') : '—' }}</td>
                                        <td>
                                            @php $colors = ['pending'=>'secondary','active'=>'success','expired'=>'danger','cancelled'=>'dark','transferred'=>'info']; @endphp
                                            <span class="badge badge-{{ $colors[$d->status] ?? 'secondary' }}">{{ $d->status }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $d->source === 'admin_import' ? 'warning' : 'light' }}">
                                                {{ $d->source === 'admin_import' ? 'Import' : 'Đơn hàng' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.domains.show', $d->id) }}" class="btn btn-sm btn-info">Xem</a>
                                            <form method="POST" action="{{ route('admin.domains.destroy', $d->id) }}" class="d-inline"
                                                onsubmit="return confirm('Xoá {{ $d->domain_name }}?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger">Xoá</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">Chưa có tên miền nào.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $domains->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
