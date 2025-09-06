{{-- resources/views/admin/provisions/index.blade.php --}}
@extends('layouts.admin.index')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <!-- Breadcrumb -->
                <div class="card mb-3">
                    <div class="card-body">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Quản lý Provisions</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['pending'] }}</h3>
                        <p>Đang chờ</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="{{ route('admin.provisions.pending') }}" class="small-box-footer">
                        Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['processing'] }}</h3>
                        <p>Đang xử lý</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['completed'] }}</h3>
                        <p>Hoàn thành</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats['failed'] }}</h3>
                        <p>Thất bại</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif
            </div>

            <!-- Main Content -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Service Provisions</h3>
                        <div class="card-tools">
                            <a href="{{ route('admin.provisions.pending') }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-clock mr-1"></i> Đang chờ ({{ $stats['pending'] }})
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <form action="{{ route('admin.provisions.index') }}" method="GET" class="form-inline">
                                    <div class="input-group mr-2">
                                        <input type="text" class="form-control" name="searchText" 
                                               placeholder="Tìm kiếm..." value="{{ request('searchText') }}">
                                        <select name="searchBy" class="form-control">
                                            <option value="customer" {{ request('searchBy') == 'customer' ? 'selected' : '' }}>Khách hàng</option>
                                            <option value="product" {{ request('searchBy') == 'product' ? 'selected' : '' }}>Sản phẩm</option>
                                            <option value="id" {{ request('searchBy') == 'id' ? 'selected' : '' }}>ID</option>
                                        </select>
                                    </div>
                                    
                                    <select name="status" class="form-control mr-2">
                                        <option value="">-- Trạng thái --</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang chờ</option>
                                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                                    </select>

                                    <select name="type" class="form-control mr-2">
                                        <option value="">-- Loại --</option>
                                        <option value="digital" {{ request('type') == 'digital' ? 'selected' : '' }}>Kỹ thuật số</option>
                                        <option value="physical" {{ request('type') == 'physical' ? 'selected' : '' }}>Vật lý</option>
                                        <option value="service" {{ request('type') == 'service' ? 'selected' : '' }}>Dịch vụ</option>
                                    </select>

                                    <button type="submit" class="btn btn-default mr-2">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    
                                    <a href="{{ route('admin.provisions.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </form>
                            </div>
                            <div class="col-md-4 text-right">
                                <select id="sort-by" class="form-control" onchange="sortProvisions(this.value)">
                                    <option value="id-desc" {{ request('sortBy') == 'id' && request('orderBy') == 'desc' ? 'selected' : '' }}>Mới nhất</option>
                                    <option value="id-asc" {{ request('sortBy') == 'id' && request('orderBy') == 'asc' ? 'selected' : '' }}>Cũ nhất</option>
                                    <option value="priority-desc" {{ request('sortBy') == 'priority' && request('orderBy') == 'desc' ? 'selected' : '' }}>Ưu tiên cao</option>
                                </select>
                            </div>
                        </div>

                        @include('source.admin.provisions._table')
                    </div>
                    <div class="card-footer clearfix">
                        {{ $provisions->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('js')
<script>
    function sortProvisions(value) {
        const params = new URLSearchParams(window.location.search);
        const [sortBy, orderBy] = value.split('-');
        params.set('sortBy', sortBy);
        params.set('orderBy', orderBy);
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    }

    function startProcessing(provisionId) {
        if (!confirm('Bắt đầu xử lý provision này?')) return;
        
        fetch(`/admin/provisions/${provisionId}/start-processing`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }

    function completeProvision(provisionId) {
        if (!confirm('Đánh dấu provision này là hoàn thành?')) return;
        
        fetch(`/admin/provisions/${provisionId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                delivery_method: 'manual'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
</script>
@endpush