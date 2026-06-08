@extends('layouts.admin.index')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-body">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Dịch vụ đang chạy</li>
                        </ol>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="col-12"><div class="alert alert-success">{{ session('success') }}</div></div>
            @endif
            @if (session('info'))
                <div class="col-12"><div class="alert alert-info">{{ session('info') }}</div></div>
            @endif

            <!-- Stats -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner"><h3>{{ $stats['active'] }}</h3><p>Đang hoạt động</p></div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner"><h3>{{ $stats['expiring'] }}</h3><p>Sắp hết hạn (30 ngày)</p></div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <a href="{{ route('admin.services.index', ['expiring' => 1]) }}" class="small-box-footer">
                        Xem <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner"><h3>{{ $stats['suspended'] }}</h3><p>Tạm ngừng</p></div>
                    <div class="icon"><i class="fas fa-pause-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner"><h3>{{ $stats['total'] }}</h3><p>Tổng dịch vụ</p></div>
                    <div class="icon"><i class="fas fa-server"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <form method="GET" class="form-inline">
                    <input type="text" name="searchText" class="form-control mr-2 mb-2" placeholder="Tên/email khách"
                           value="{{ request('searchText') }}">
                    <select name="type" class="form-control mr-2 mb-2">
                        <option value="">-- Loại --</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ strtoupper($t) }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-control mr-2 mb-2">
                        <option value="">-- Trạng thái --</option>
                        @foreach (['active' => 'Đang hoạt động', 'expired' => 'Hết hạn', 'suspended' => 'Tạm ngừng', 'cancelled' => 'Đã hủy'] as $k => $v)
                            <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary mr-2 mb-2"><i class="fas fa-search"></i> Lọc</button>
                    <a href="{{ route('admin.services.index') }}" class="btn btn-default mb-2">Xóa lọc</a>
                </form>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Khách hàng</th>
                            <th>Sản phẩm</th>
                            <th>Loại</th>
                            <th>Trạng thái</th>
                            <th>Bắt đầu</th>
                            <th>Hết hạn</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $s)
                            <tr>
                                <td>{{ $s->id }}</td>
                                <td>{{ $s->customer->name ?? '—' }}<br><small class="text-muted">{{ $s->customer->email ?? '' }}</small></td>
                                <td>{{ $s->product->name ?? '—' }}</td>
                                <td><span class="badge badge-dark">{{ strtoupper($s->service_type ?? 'N/A') }}</span></td>
                                <td><span class="badge badge-{{ $s->status_badge_class }}">{{ $s->status_label }}</span></td>
                                <td>{{ $s->started_at ? $s->started_at->format('d/m/Y') : '—' }}</td>
                                <td>
                                    @if ($s->expires_at)
                                        {{ $s->expires_at->format('d/m/Y') }}
                                        @php $d = $s->daysUntilExpiry(); @endphp
                                        @if ($d !== null)
                                            <br><small class="text-{{ $d < 0 ? 'danger' : ($d <= 30 ? 'warning' : 'muted') }}">
                                                {{ $d < 0 ? 'Quá ' . abs($d) . ' ngày' : 'Còn ' . $d . ' ngày' }}
                                            </small>
                                        @endif
                                    @else — @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.services.show', $s->id) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('admin.services.edit', $s->id) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Không có dịch vụ nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{ $services->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
