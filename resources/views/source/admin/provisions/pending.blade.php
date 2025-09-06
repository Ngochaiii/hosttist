{{-- resources/views/admin/provisions/pending.blade.php --}}
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
                            <li class="breadcrumb-item"><a href="{{ route('admin.provisions.index') }}">Provisions</a></li>
                            <li class="breadcrumb-item active">Đang chờ xử lý</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $pendingStats['total_pending'] }}</h3>
                        <p>Tổng đang chờ</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $pendingStats['high_priority'] }}</h3>
                        <p>Ưu tiên cao</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3>{{ $pendingStats['overdue'] }}</h3>
                        <p>Quá hạn</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $pendingStats['today'] }}</h3>
                        <p>Hôm nay</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
            </div>

            <!-- Main Content -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clock text-warning mr-2"></i>
                            Provisions Đang Chờ Xử Lý
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('admin.provisions.index') }}" class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Về trang chính
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Quick Filters -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.provisions.pending') }}" 
                                       class="btn {{ !request('priority') && !request('type') ? 'btn-primary' : 'btn-outline-primary' }}">
                                        Tất cả
                                    </a>
                                    <a href="{{ route('admin.provisions.pending', ['priority' => 'high']) }}" 
                                       class="btn {{ request('priority') == 'high' ? 'btn-danger' : 'btn-outline-danger' }}">
                                        Ưu tiên cao
                                    </a>
                                    <a href="{{ route('admin.provisions.pending', ['type' => 'digital']) }}" 
                                       class="btn {{ request('type') == 'digital' ? 'btn-info' : 'btn-outline-info' }}">
                                        Kỹ thuật số
                                    </a>
                                    <a href="{{ route('admin.provisions.pending', ['type' => 'service']) }}" 
                                       class="btn {{ request('type') == 'service' ? 'btn-success' : 'btn-outline-success' }}">
                                        Dịch vụ
                                    </a>
                                </div>
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

    // Auto refresh every 30 seconds for pending page
    setInterval(() => {
        if (!document.hidden) {
            location.reload();
        }
    }, 30000);
</script>
@endpush