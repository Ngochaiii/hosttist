{{-- resources/views/customer/services/credentials.blade.php --}}
@extends('layouts.web.default')

@section('content')
    <section class="credentials_section layout_padding">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('customer.services.index') }}">Dịch vụ của tôi</a>
                            </li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('customer.services.provision.show', $provision->id) }}">{{ $provision->product->name ?? 'Chi tiết dịch vụ' }}</a>
                            </li>
                            <li class="breadcrumb-item active">Thông tin truy cập</li>
                        </ol>
                    </nav>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Security Warning -->
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-shield fa-2x mr-3"></i>
                            <div>
                                <h5 class="alert-heading mb-1">Bảo mật thông tin</h5>
                                <p class="mb-0">Vui lòng bảo mật thông tin này và không chia sẻ với bất kỳ ai. Thay đổi
                                    mật khẩu định kỳ để đảm bảo an toàn.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Service Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h4 class="m-0">
                                <i class="fa fa-key"></i>
                                Thông tin truy cập - {{ $provision->product->name ?? 'Dịch vụ' }}
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Mã dịch vụ:</strong> #{{ $provision->id }}</p>
                                    <p><strong>Trạng thái:</strong>
                                        <span class="badge badge-success">{{ $provision->getStatusLabel() }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Ngày kích hoạt:</strong>
                                        {{ optional($provision->provisioned_at)->format('d/m/Y H:i') ?? '—' }}</p>
                                    <p><strong>Loại dịch vụ:</strong> {{ strtoupper($provision->product->type ?? 'N/A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $infoRows = collect($fields)->where('file', false);
                        $fileRows = collect($fields)->where('file', true);
                    @endphp

                    <!-- Thông tin truy cập / cấu hình -->
                    @if ($infoRows->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0"><i class="fa fa-info-circle"></i> Thông tin dịch vụ</h5>
                            </div>
                            <div class="card-body">
                                @foreach ($infoRows as $i => $row)
                                    <div class="credential-item mb-3">
                                        <label class="credential-label">{{ $row['label'] }}:</label>
                                        <div class="credential-value">
                                            @if ($row['secret'])
                                                <input type="password" id="cred-{{ $i }}"
                                                    class="form-control credential-input password-field"
                                                    value="{{ $row['value'] }}" readonly>
                                                <button class="btn btn-outline-secondary btn-sm toggle-password"
                                                    data-target="#cred-{{ $i }}">
                                                    <i class="fa fa-eye"></i> Hiện
                                                </button>
                                            @else
                                                <input type="text" class="form-control credential-input"
                                                    value="{{ $row['value'] }}" readonly>
                                            @endif
                                            <button class="btn btn-outline-primary btn-sm copy-btn"
                                                data-clipboard-text="{{ $row['value'] }}">
                                                <i class="fa fa-copy"></i> Sao chép
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Download SSL files -->
                    @if ($fileRows->isNotEmpty())
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="m-0"><i class="fa fa-download"></i> Tải file chứng chỉ SSL</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($fileRows as $row)
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('customer.services.ssl.download', [$provision->id, $row['kind']]) }}"
                                                class="btn btn-outline-primary btn-block">
                                                <i class="fa fa-certificate"></i> {{ $row['label'] }}
                                            </a>
                                        </div>
                                    @endforeach
                                    <div class="col-md-12">
                                        <a href="{{ route('customer.services.ssl.download', [$provision->id, 'all']) }}"
                                            class="btn btn-success btn-block">
                                            <i class="fa fa-file-archive-o"></i> Tải tất cả (.tar.gz)
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($infoRows->isEmpty() && $fileRows->isEmpty())
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fa fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h4>Thông tin truy cập chưa có sẵn</h4>
                                <p class="text-muted">Vui lòng liên hệ bộ phận hỗ trợ.</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="m-0">Thao tác</h5>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('customer.services.provision.show', $provision->id) }}"
                                class="btn btn-outline-primary btn-block mb-2">
                                <i class="fa fa-arrow-left"></i> Quay lại chi tiết
                            </a>
                            <a href="{{ route('customer.services.index') }}"
                                class="btn btn-outline-secondary btn-block mb-2">
                                <i class="fa fa-list"></i> Danh sách dịch vụ
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-info btn-block">
                                <i class="fa fa-print"></i> In thông tin
                            </button>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="m-0"><i class="fa fa-shield"></i> Lời khuyên bảo mật</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled small">
                                <li class="mb-2"><i class="fa fa-check text-success"></i> Không chia sẻ thông tin đăng nhập với người khác</li>
                                <li class="mb-2"><i class="fa fa-check text-success"></i> Thay đổi mật khẩu định kỳ (3-6 tháng)</li>
                                <li class="mb-2"><i class="fa fa-check text-success"></i> Sử dụng kết nối an toàn (HTTPS, SFTP)</li>
                                <li><i class="fa fa-check text-success"></i> Báo cáo ngay nếu phát hiện bất thường</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('header_css')
        <style>
            .credential-item { border: 1px solid #e9ecef; border-radius: 8px; padding: 16px; background: #f8f9fa; }
            .credential-label { font-weight: 600; color: #495057; margin-bottom: 8px; display: block; }
            .credential-value { display: flex; gap: 10px; }
            .credential-input { font-family: 'Courier New', monospace; background-color: #fff; border: 2px solid #dee2e6; }
            .copy-btn { white-space: nowrap; }
            @media print { .btn, .card-header, nav, .alert-warning { display: none !important; } .card { border: 1px solid #000 !important; } }
            .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
            .toast { background: #28a745; color: white; padding: 12px 20px; border-radius: 5px; margin-bottom: 10px; opacity: 0; transform: translateX(100%); transition: all 0.3s ease; }
            .toast.show { opacity: 1; transform: translateX(0); }
        </style>
    @endpush

    @push('footer_js')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
        <script>
            $(document).ready(function() {
                var clipboard = new ClipboardJS('.copy-btn');
                clipboard.on('success', function(e) { showToast('Đã sao chép vào clipboard!'); e.clearSelection(); });
                clipboard.on('error', function() { showToast('Lỗi khi sao chép. Vui lòng thử lại.', 'error'); });

                $('.toggle-password').on('click', function() {
                    const target = $($(this).data('target'));
                    if (target.attr('type') === 'password') {
                        target.attr('type', 'text');
                        $(this).html('<i class="fa fa-eye-slash"></i> Ẩn');
                    } else {
                        target.attr('type', 'password');
                        $(this).html('<i class="fa fa-eye"></i> Hiện');
                    }
                });

                function showToast(message, type = 'success') {
                    const toastContainer = $('.toast-container').length ? $('.toast-container') : $('<div class="toast-container"></div>').appendTo('body');
                    const bgColor = type === 'success' ? '#28a745' : '#dc3545';
                    const toast = $('<div class="toast" style="background: ' + bgColor + '">' + message + '</div>');
                    toastContainer.append(toast);
                    setTimeout(function() { toast.addClass('show'); }, 100);
                    setTimeout(function() { toast.removeClass('show'); setTimeout(function() { toast.remove(); }, 300); }, 3000);
                }
            });
        </script>
    @endpush
@endsection
