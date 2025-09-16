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
                                    href="{{ route('customer.services.show', $provision->id) }}">{{ $provision->product->name ?? 'Chi tiết dịch vụ' }}</a>
                            </li>
                            <li class="breadcrumb-item active">Thông tin truy cập</li>
                        </ol>
                    </nav>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
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
                                        {{ $provision->provisioned_at->format('d/m/Y H:i') }}</p>
                                    <p><strong>Loại dịch vụ:</strong> {{ ucfirst($provision->product->type ?? 'N/A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credentials Display -->
                    @php
                        $provisionData = json_decode($provision->provision_data, true) ?? [];
                        $credentials = $provisionData['credentials'] ?? [];
                    @endphp

                    @if (!empty($credentials))
                        <!-- Main Credentials -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="m-0">
                                    <i class="fa fa-info-circle"></i>
                                    Thông tin đăng nhập chính
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="credentials-container">
                                    @if (isset($credentials['url']) || isset($credentials['server']) || isset($credentials['host']))
                                        <div class="credential-item mb-4">
                                            <label class="credential-label">Địa chỉ truy cập:</label>
                                            <div class="credential-value">
                                                @php
                                                    $accessUrl =
                                                        $credentials['url'] ??
                                                        ($credentials['server'] ?? ($credentials['host'] ?? ''));
                                                @endphp
                                                <input type="text" class="form-control credential-input"
                                                    value="{{ $accessUrl }}" readonly>
                                                <button class="btn btn-outline-primary btn-sm copy-btn"
                                                    data-clipboard-text="{{ $accessUrl }}">
                                                    <i class="fa fa-copy"></i> Sao chép
                                                </button>
                                                @if (filter_var($accessUrl, FILTER_VALIDATE_URL))
                                                    <a href="{{ $accessUrl }}" target="_blank"
                                                        class="btn btn-outline-success btn-sm">
                                                        <i class="fa fa-external-link"></i> Mở
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if (isset($credentials['username']) || isset($credentials['user']))
                                        <div class="credential-item mb-4">
                                            <label class="credential-label">Tên đăng nhập:</label>
                                            <div class="credential-value">
                                                @php
                                                    $username =
                                                        $credentials['username'] ?? ($credentials['user'] ?? '');
                                                @endphp
                                                <input type="text" class="form-control credential-input"
                                                    value="{{ $username }}" readonly>
                                                <button class="btn btn-outline-primary btn-sm copy-btn"
                                                    data-clipboard-text="{{ $username }}">
                                                    <i class="fa fa-copy"></i> Sao chép
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    @if (isset($credentials['password']) || isset($credentials['pass']))
                                        <div class="credential-item mb-4">
                                            <label class="credential-label">Mật khẩu:</label>
                                            <div class="credential-value">
                                                @php
                                                    $password =
                                                        $credentials['password'] ?? ($credentials['pass'] ?? '');
                                                @endphp
                                                <input type="password" class="form-control credential-input password-field"
                                                    value="{{ $password }}" readonly id="main-password">
                                                <button class="btn btn-outline-secondary btn-sm toggle-password"
                                                    data-target="#main-password">
                                                    <i class="fa fa-eye"></i> Hiện
                                                </button>
                                                <button class="btn btn-outline-primary btn-sm copy-btn"
                                                    data-clipboard-text="{{ $password }}">
                                                    <i class="fa fa-copy"></i> Sao chép
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Additional Credentials (FTP, Database, etc.) -->
                        @if (isset($credentials['ftp']) || isset($credentials['database']) || isset($credentials['email']))
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="m-0">
                                        <i class="fa fa-cogs"></i>
                                        Thông tin bổ sung
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- FTP Access -->
                                    @if (isset($credentials['ftp']) && is_array($credentials['ftp']))
                                        <div class="additional-credential-section mb-4">
                                            <h6 class="text-primary mb-3">
                                                <i class="fa fa-folder"></i> Truy cập FTP
                                            </h6>
                                            <div class="row">
                                                @if (isset($credentials['ftp']['host']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">FTP Host:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control"
                                                                value="{{ $credentials['ftp']['host'] }}" readonly>
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['ftp']['host'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (isset($credentials['ftp']['username']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">FTP Username:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control"
                                                                value="{{ $credentials['ftp']['username'] }}" readonly>
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['ftp']['username'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (isset($credentials['ftp']['password']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">FTP Password:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="password" class="form-control password-field"
                                                                value="{{ $credentials['ftp']['password'] }}" readonly
                                                                id="ftp-password">
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-secondary toggle-password"
                                                                    data-target="#ftp-password">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['ftp']['password'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (isset($credentials['ftp']['port']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">FTP Port:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control"
                                                                value="{{ $credentials['ftp']['port'] }}" readonly>
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['ftp']['port'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Database Access -->
                                    @if (isset($credentials['database']) && is_array($credentials['database']))
                                        <div class="additional-credential-section mb-4">
                                            <h6 class="text-success mb-3">
                                                <i class="fa fa-database"></i> Truy cập Database
                                            </h6>
                                            <div class="row">
                                                @if (isset($credentials['database']['host']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">DB Host:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control"
                                                                value="{{ $credentials['database']['host'] }}" readonly>
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['database']['host'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (isset($credentials['database']['name']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">Database Name:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control"
                                                                value="{{ $credentials['database']['name'] }}" readonly>
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['database']['name'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (isset($credentials['database']['username']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">DB Username:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control"
                                                                value="{{ $credentials['database']['username'] }}"
                                                                readonly>
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['database']['username'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (isset($credentials['database']['password']))
                                                    <div class="col-md-6 mb-3">
                                                        <label class="small text-muted">DB Password:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="password" class="form-control password-field"
                                                                value="{{ $credentials['database']['password'] }}"
                                                                readonly id="db-password">
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-secondary toggle-password"
                                                                    data-target="#db-password">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['database']['password'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <!-- API Keys or Additional Info -->
                                    @if (isset($credentials['api_key']) || isset($credentials['secret_key']))
                                        <div class="additional-credential-section">
                                            <h6 class="text-info mb-3">
                                                <i class="fa fa-code"></i> API Keys
                                            </h6>
                                            <div class="row">
                                                @if (isset($credentials['api_key']))
                                                    <div class="col-md-12 mb-3">
                                                        <label class="small text-muted">API Key:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="password" class="form-control password-field"
                                                                value="{{ $credentials['api_key'] }}" readonly
                                                                id="api-key">
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-secondary toggle-password"
                                                                    data-target="#api-key">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['api_key'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (isset($credentials['secret_key']))
                                                    <div class="col-md-12 mb-3">
                                                        <label class="small text-muted">Secret Key:</label>
                                                        <div class="input-group input-group-sm">
                                                            <input type="password" class="form-control password-field"
                                                                value="{{ $credentials['secret_key'] }}" readonly
                                                                id="secret-key">
                                                            <div class="input-group-append">
                                                                <button class="btn btn-outline-secondary toggle-password"
                                                                    data-target="#secret-key">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-outline-primary copy-btn"
                                                                    data-clipboard-text="{{ $credentials['secret_key'] }}">
                                                                    <i class="fa fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Notes and Instructions -->
                        @if (isset($provisionData['instructions']) || isset($provisionData['notes']))
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="m-0">
                                        <i class="fa fa-lightbulb-o"></i>
                                        Hướng dẫn sử dụng
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if (isset($provisionData['instructions']))
                                        <div class="alert alert-info">
                                            <strong>Hướng dẫn:</strong><br>
                                            {!! nl2br(e($provisionData['instructions'])) !!}
                                        </div>
                                    @endif

                                    @if (isset($provisionData['notes']))
                                        <div class="alert alert-warning">
                                            <strong>Lưu ý:</strong><br>
                                            {!! nl2br(e($provisionData['notes'])) !!}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($service->type === 'ssl' && isset($credentials['ssl_files']))
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="m-0">
                                        <i class="fa fa-download"></i>
                                        Download SSL Files
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Download your SSL certificate files to install on your
                                        server:</p>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('customer.services.ssl.download', [$provision->id, 'certificate']) }}"
                                                class="btn btn-outline-primary btn-block">
                                                <i class="fa fa-certificate"></i> Download Certificate (.crt)
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('customer.services.ssl.download', [$provision->id, 'private_key']) }}"
                                                class="btn btn-outline-warning btn-block">
                                                <i class="fa fa-key"></i> Download Private Key (.key)
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('customer.services.ssl.download', [$provision->id, 'ca_bundle']) }}"
                                                class="btn btn-outline-info btn-block">
                                                <i class="fa fa-chain"></i> Download CA Bundle (.crt)
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('customer.services.ssl.download', [$provision->id, 'all']) }}"
                                                class="btn btn-success btn-block">
                                                <i class="fa fa-archive"></i> Download All Files (.zip)
                                            </a>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning mt-3">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong>Important:</strong> Keep your private key secure! Never share it or upload
                                        it to unsecured locations.
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fa fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h4>Thông tin truy cập chưa có sẵn</h4>
                                <p class="text-muted">Credentials cho dịch vụ này chưa được cung cấp. Vui lòng liên hệ bộ
                                    phận hỗ trợ.</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="m-0">Thao tác</h5>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('customer.services.show', $provision->id) }}"
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

                    <!-- Security Tips -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="m-0">
                                <i class="fa fa-shield"></i>
                                Lời khuyên bảo mật
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled small">
                                <li class="mb-2">
                                    <i class="fa fa-check text-success"></i>
                                    Không chia sẻ thông tin đăng nhập với người khác
                                </li>
                                <li class="mb-2">
                                    <i class="fa fa-check text-success"></i>
                                    Thay đổi mật khẩu định kỳ (3-6 tháng)
                                </li>
                                <li class="mb-2">
                                    <i class="fa fa-check text-success"></i>
                                    Sử dụng kết nối an toàn (HTTPS, SFTP)
                                </li>
                                <li class="mb-2">
                                    <i class="fa fa-check text-success"></i>
                                    Đăng xuất sau khi sử dụng
                                </li>
                                <li>
                                    <i class="fa fa-check text-success"></i>
                                    Báo cáo ngay nếu phát hiện bất thường
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <style>
            .credential-item {
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 20px;
                background: #f8f9fa;
            }

            .credential-label {
                font-weight: 600;
                color: #495057;
                margin-bottom: 10px;
                display: block;
            }

            .credential-value {
                display: flex;
                gap: 10px;
            }

            .credential-input {
                font-family: 'Courier New', monospace;
                background-color: #fff;
                border: 2px solid #dee2e6;
            }

            .credential-input:focus {
                border-color: #007bff;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
            }

            .copy-btn {
                white-space: nowrap;
            }

            .additional-credential-section {
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 20px;
                background: #ffffff;
            }

            .additional-credential-section h6 {
                border-bottom: 2px solid #e9ecef;
                padding-bottom: 10px;
                margin-bottom: 15px;
            }

            @media print {

                .btn,
                .card-header,
                nav,
                .alert-warning {
                    display: none !important;
                }

                .card {
                    border: 1px solid #000 !important;
                }

                .credential-input {
                    border: 1px solid #000 !important;
                }
            }

            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            }

            .toast {
                background: #28a745;
                color: white;
                padding: 12px 20px;
                border-radius: 5px;
                margin-bottom: 10px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            }

            .toast.show {
                opacity: 1;
                transform: translateX(0);
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
        <script>
            $(document).ready(function() {
                // Initialize clipboard
                var clipboard = new ClipboardJS('.copy-btn');

                clipboard.on('success', function(e) {
                    showToast('Đã sao chép vào clipboard!');
                    e.clearSelection();
                });

                clipboard.on('error', function(e) {
                    showToast('Lỗi khi sao chép. Vui lòng thử lại.', 'error');
                });

                // Toggle password visibility
                $('.toggle-password').on('click', function() {
                    const target = $($(this).data('target'));
                    const icon = $(this).find('i');

                    if (target.attr('type') === 'password') {
                        target.attr('type', 'text');
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');
                        $(this).html('<i class="fa fa-eye-slash"></i> Ẩn');
                    } else {
                        target.attr('type', 'password');
                        icon.removeClass('fa-eye-slash').addClass('fa-eye');
                        $(this).html('<i class="fa fa-eye"></i> Hiện');
                    }
                });

                // Show toast notification
                function showToast(message, type = 'success') {
                    const toastContainer = $('.toast-container').length ?
                        $('.toast-container') :
                        $('<div class="toast-container"></div>').appendTo('body');

                    const bgColor = type === 'success' ? '#28a745' : '#dc3545';
                    const toast = $(`
            <div class="toast" style="background: ${bgColor}">
                <i class="fa fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                ${message}
            </div>
        `);

                    toastContainer.append(toast);

                    setTimeout(function() {
                        toast.addClass('show');
                    }, 100);

                    setTimeout(function() {
                        toast.removeClass('show');
                        setTimeout(function() {
                            toast.remove();
                        }, 300);
                    }, 3000);
                }
            });
        </script>
    @endpush
@endsection
