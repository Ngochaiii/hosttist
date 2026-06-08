@extends('layouts.admin.index')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Dịch vụ đang chạy</a></li>
                            <li class="breadcrumb-item active">#{{ $service->id }}</li>
                        </ol>
                        <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Sửa thông số
                        </a>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="col-12"><div class="alert alert-success">{{ session('success') }}</div></div>
            @endif
            @if (session('error'))
                <div class="col-12"><div class="alert alert-danger">{{ session('error') }}</div></div>
            @endif

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Thông số {{ strtoupper($service->service_type ?? '') }}</h3>
                    </div>
                    <div class="card-body">
                        @if (!$service->provision)
                            <div class="alert alert-warning mb-0">Dịch vụ legacy chưa có dữ liệu provision.</div>
                        @else
                            <table class="table table-sm table-borderless">
                                @foreach ($schema as $field)
                                    @php $v = $values[$field['name']] ?? ''; @endphp
                                    <tr>
                                        <th style="width:35%">{{ $field['label'] }}</th>
                                        <td>
                                            @if (!empty($field['file']))
                                                @if ($v !== '')
                                                    <a href="{{ route('admin.services.ssl.download', [$service->id, $field['name']]) }}"
                                                       class="btn btn-xs btn-outline-secondary">
                                                        <i class="fas fa-download"></i> Tải file
                                                    </a>
                                                    <small class="text-muted">({{ strlen($v) }} bytes)</small>
                                                @else <span class="text-muted">— chưa có —</span> @endif
                                            @elseif (!empty($field['encrypted']))
                                                @if ($v !== '')
                                                    <span class="js-secret" data-shown="0">
                                                        <code class="secret-mask">••••••••</code>
                                                        <code class="secret-value d-none">{{ $v }}</code>
                                                    </span>
                                                    <button type="button" class="btn btn-xs btn-link js-reveal">hiện</button>
                                                @else <span class="text-muted">— chưa đặt —</span> @endif
                                            @elseif ($field['type'] === 'textarea')
                                                <pre class="mb-0" style="white-space:pre-wrap">{{ $v ?: '—' }}</pre>
                                            @else
                                                {{ $v !== '' ? $v : '—' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </div>
                </div>

                @if ($logs->isNotEmpty())
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Nhật ký</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-sm">
                                <thead><tr><th>Thời gian</th><th>Hành động</th><th>Bởi</th></tr></thead>
                                <tbody>
                                    @foreach ($logs as $log)
                                        <tr>
                                            <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $log->getActionLabel() }}</td>
                                            <td>{{ $log->performed_by ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Tổng quan</h3></div>
                    <div class="card-body">
                        <p><strong>Khách:</strong> {{ $service->customer->name ?? '—' }}</p>
                        <p><strong>Email:</strong> {{ $service->customer->email ?? '—' }}</p>
                        <p><strong>Sản phẩm:</strong> {{ $service->product->name ?? '—' }}</p>
                        <p><strong>Trạng thái:</strong> <span class="badge badge-{{ $service->status_badge_class }}">{{ $service->status_label }}</span></p>
                        <p><strong>Bắt đầu:</strong> {{ $service->started_at?->format('d/m/Y') ?? '—' }}</p>
                        <p><strong>Hết hạn:</strong> {{ $service->expires_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Đổi trạng thái</h3></div>
                    <div class="card-body">
                        <form action="{{ route('admin.services.status', $service->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="active" {{ $service->status === 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                                    <option value="suspended" {{ $service->status === 'suspended' ? 'selected' : '' }}>Tạm ngừng</option>
                                    <option value="cancelled" {{ $service->status === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="text" name="reason" class="form-control" placeholder="Lý do (tùy chọn)">
                            </div>
                            <button class="btn btn-warning btn-block"><i class="fas fa-exchange-alt"></i> Cập nhật trạng thái</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.js-reveal').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var wrap = btn.previousElementSibling;
        var mask = wrap.querySelector('.secret-mask');
        var val  = wrap.querySelector('.secret-value');
        var shown = wrap.getAttribute('data-shown') === '1';
        mask.classList.toggle('d-none', !shown);
        val.classList.toggle('d-none', shown);
        wrap.setAttribute('data-shown', shown ? '0' : '1');
        btn.textContent = shown ? 'hiện' : 'ẩn';
    });
});
</script>
@endsection
