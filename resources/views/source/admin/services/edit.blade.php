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
                            <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Dịch vụ đang chạy</a></li>
                            <li class="breadcrumb-item active">Sửa #{{ $service->id }}</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Thông số {{ strtoupper($service->service_type ?? '') }} — {{ $service->product->name ?? 'Dịch vụ #' . $service->id }}
                        </h3>
                    </div>

                    @if ($errors->any())
                        <div class="card-body pb-0">
                            <div class="alert alert-danger"><ul class="mb-0">
                                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                            </ul></div>
                        </div>
                    @endif

                    <form action="{{ route('admin.services.update', $service->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            {{-- Vòng đời: ngày bắt đầu & hết hạn (cột trên customer_services) --}}
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Ngày bắt đầu</label>
                                    <input type="date" name="started_at" class="form-control"
                                           value="{{ old('started_at', $service->started_at?->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Ngày hết hạn</label>
                                    <input type="date" name="expires_at" class="form-control"
                                           value="{{ old('expires_at', $service->expires_at?->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <hr>

                            @if (!$service->provision)
                                <div class="alert alert-warning mb-0">
                                    Dịch vụ này chưa có provision liên kết (dữ liệu legacy) nên chưa thể lưu thông số kỹ thuật — chỉ sửa được ngày bắt đầu/hết hạn.
                                </div>
                            @else
                                @foreach ($schema as $field)
                                    @include('source.admin.services._fields', ['field' => $field, 'values' => $values])
                                @endforeach
                            @endif
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
                            <a href="{{ route('admin.services.show', $service->id) }}" class="btn btn-secondary">Quay lại</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Thông tin</h3></div>
                    <div class="card-body">
                        <p><strong>Khách:</strong> {{ $service->customer->name ?? '—' }}</p>
                        <p><strong>Trạng thái:</strong> <span class="badge badge-{{ $service->status_badge_class }}">{{ $service->status_label }}</span></p>
                        <p class="text-muted mb-0"><small>Ngày bắt đầu/hết hạn sửa ở form bên trái.</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
