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
                                <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Danh mục</a></li>
                                <li class="breadcrumb-item active">Chỉnh sửa danh mục</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <!-- Thông báo lỗi -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Thông báo thành công -->
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                </div>

                <div class="col-md-8">
                    <!-- Form chỉnh sửa danh mục -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Chỉnh sửa danh mục: {{ $category->name }}</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>

                        <form action="{{ route('admin.categories.update', $category->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $category->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                        id="slug" name="slug" value="{{ old('slug', $category->slug) }}"
                                        placeholder="Tự động tạo nếu để trống">
                                    @error('slug')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="parent_id">Danh mục cha</label>
                                    <select class="form-control select2bs4 @error('parent_id') is-invalid @enderror"
                                        id="parent_id" name="parent_id">
                                        <option value="">-- Không có --</option>
                                        @foreach ($parentCategories as $parentCategory)
                                            @if ($parentCategory->id != $category->id)
                                                <option value="{{ $parentCategory->id }}"
                                                    {{ old('parent_id', $category->parent_id) == $parentCategory->id ? 'selected' : '' }}>
                                                    {{ $parentCategory->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- THÊM PHẦN CHỌN LOẠI DỊCH VỤ -->
                                <div class="form-group">
                                    <label for="service_type">
                                        <i class="fas fa-cogs"></i> Loại dịch vụ
                                        <small class="text-muted">(Tùy chọn - Để hệ thống yêu cầu thông tin phù hợp từ khách
                                            hàng)</small>
                                    </label>
                                    <select class="form-control @error('service_type') is-invalid @enderror"
                                        id="service_type" name="service_type">
                                        <option value="">-- Không yêu cầu thông tin đặc biệt --</option>
                                        @php
                                            $currentType = '';
                                            if ($category->meta_data) {
                                                $metaData = is_string($category->meta_data)
                                                    ? json_decode($category->meta_data, true)
                                                    : $category->meta_data;
                                                $currentType = $metaData['service_type'] ?? '';
                                            }
                                            $currentType = old('service_type', $currentType);
                                        @endphp
                                        <option value="ssl" {{ $currentType == 'ssl' ? 'selected' : '' }}>
                                            🔒 SSL Certificate (yêu cầu domain)
                                        </option>
                                        <option value="vps" {{ $currentType == 'vps' ? 'selected' : '' }}>
                                            💻 VPS/Cloud Server (yêu cầu username, OS)
                                        </option>
                                        <option value="domain" {{ $currentType == 'domain' ? 'selected' : '' }}>
                                            🌐 Tên miền (yêu cầu domain)
                                        </option>
                                        <option value="hosting" {{ $currentType == 'hosting' ? 'selected' : '' }}>
                                            🗄️ Web Hosting (domain tùy chọn)
                                        </option>
                                        <option value="email" {{ $currentType == 'email' ? 'selected' : '' }}>
                                            📧 Email doanh nghiệp (yêu cầu domain, số lượng)
                                        </option>
                                        <option value="web_design" {{ $currentType == 'web_design' ? 'selected' : '' }}>
                                            🎨 Thiết kế website (yêu cầu SĐT)
                                        </option>
                                        <option value="advertising" {{ $currentType == 'advertising' ? 'selected' : '' }}>
                                            📢 Chạy quảng cáo (yêu cầu link FB/TikTok)
                                        </option>
                                        <option value="seo" {{ $currentType == 'seo' ? 'selected' : '' }}>
                                            🔍 Dịch vụ SEO (yêu cầu website URL, keywords)
                                        </option>
                                    </select>
                                    @error('service_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Preview các fields sẽ yêu cầu -->
                                <div id="fields_preview" class="alert alert-info" style="display: none;">
                                    <h6><i class="fas fa-info-circle"></i> Thông tin sẽ yêu cầu từ khách hàng khi đặt hàng:
                                    </h6>
                                    <div id="fields_list"></div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Mô tả</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                        rows="3">{{ old('description', $category->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="image">Hình ảnh</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file"
                                                class="custom-file-input @error('image') is-invalid @enderror"
                                                id="image" name="image" accept="image/*">
                                            <label class="custom-file-label" for="image">Chọn file</label>
                                        </div>
                                    </div>
                                    @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror

                                    @if ($category->image)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $category->image) }}"
                                                alt="{{ $category->name }}" class="img-thumbnail"
                                                style="max-height: 150px">
                                            <p class="text-muted small">Hình ảnh hiện tại. Tải lên ảnh mới nếu muốn thay
                                                thế.</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label for="sort_order">Thứ tự hiển thị</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                        id="sort_order" name="sort_order"
                                        value="{{ old('sort_order', $category->sort_order) }}" min="0">
                                    @error('sort_order')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="status"
                                            name="status" value="active"
                                            {{ old('status', $category->status) == 'active' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="status">
                                            <span class="text-success">Kích hoạt</span> / <span class="text-danger">Vô
                                                hiệu</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Cập nhật
                                </button>
                                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Thông tin danh mục -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin danh mục</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-5">ID:</dt>
                                <dd class="col-sm-7">{{ $category->id }}</dd>

                                <dt class="col-sm-5">Trạng thái:</dt>
                                <dd class="col-sm-7">
                                    <span
                                        class="badge {{ $category->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                        {{ $category->status == 'active' ? 'Kích hoạt' : 'Vô hiệu' }}
                                    </span>
                                </dd>

                                <dt class="col-sm-5">Loại dịch vụ:</dt>
                                <dd class="col-sm-7">
                                    @php
                                        $serviceLabels = [
                                            'ssl' => ['SSL Certificate', 'warning'],
                                            'vps' => ['VPS/Cloud', 'info'],
                                            'domain' => ['Tên miền', 'success'],
                                            'hosting' => ['Web Hosting', 'primary'],
                                            'email' => ['Email', 'secondary'],
                                            'web_design' => ['Thiết kế Web', 'danger'],
                                            'advertising' => ['Quảng cáo', 'warning'],
                                            'seo' => ['SEO', 'dark'],
                                        ];
                                    @endphp
                                    @if ($currentType && isset($serviceLabels[$currentType]))
                                        <span class="badge badge-{{ $serviceLabels[$currentType][1] }}">
                                            {{ $serviceLabels[$currentType][0] }}
                                        </span>
                                    @else
                                        <span class="text-muted">Không xác định</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Ngày tạo:</dt>
                                <dd class="col-sm-7">{{ $category->created_at->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-5">Cập nhật:</dt>
                                <dd class="col-sm-7">{{ $category->updated_at->format('d/m/Y H:i') }}</dd>

                                <dt class="col-sm-5">Danh mục con:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-info">{{ $category->children->count() }}</span>
                                </dd>

                                <dt class="col-sm-5">Sản phẩm:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-info">{{ $category->products->count() }}</span>
                                </dd>
                            </dl>
                        </div>
                    </div>

                    <!-- Danh mục con -->
                    @if ($category->children->count() > 0)
                        <div class="card card-secondary">
                            <div class="card-header">
                                <h3 class="card-title">Danh mục con</h3>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    @foreach ($category->children as $child)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $child->name }}
                                            <a href="{{ route('admin.categories.edit', $child->id) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- Vùng nguy hiểm -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Vùng nguy hiểm</h3>
                        </div>
                        <div class="card-body">
                            @if ($category->children->count() > 0)
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Không thể xóa danh mục này vì có {{ $category->children->count() }} danh mục con.
                                </div>
                            @elseif($category->products->count() > 0)
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Không thể xóa danh mục này vì có {{ $category->products->count() }} sản phẩm.
                                </div>
                            @else
                                <p class="text-danger">Cảnh báo: Hành động này không thể khôi phục!</p>
                                <form id="deleteForm" action="{{ route('admin.categories.destroy', $category->id) }}"
                                    method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-block delete-btn">
                                        <i class="fas fa-trash mr-2"></i>Xóa danh mục này
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script>
        // Service fields configuration
        const serviceFields = {
            ssl: {
                label: 'SSL Certificate',
                fields: [
                    '✓ Domain (bắt buộc): Tên miền cần cài SSL'
                ]
            },
            vps: {
                label: 'VPS/Cloud Server',
                fields: [
                    '✓ Username (bắt buộc): Tên đăng nhập VPS',
                    '✓ Hệ điều hành: Ubuntu, CentOS, Debian, Windows'
                ]
            },
            domain: {
                label: 'Tên miền',
                fields: [
                    '✓ Domain (bắt buộc): Tên miền muốn đăng ký',
                    '✓ Quản lý DNS: Tùy chọn sử dụng DNS'
                ]
            },
            hosting: {
                label: 'Web Hosting',
                fields: [
                    '✓ Domain (tùy chọn): Tên miền sử dụng',
                    '✓ Chuyển hosting: Hỗ trợ chuyển từ hosting cũ'
                ]
            },
            email: {
                label: 'Email doanh nghiệp',
                fields: [
                    '✓ Domain (bắt buộc): Tên miền cho email',
                    '✓ Số lượng tài khoản (bắt buộc)',
                    '✓ Email chính (tùy chọn)'
                ]
            },
            web_design: {
                label: 'Thiết kế website',
                fields: [
                    '✓ Số điện thoại (bắt buộc)',
                    '✓ Loại hình kinh doanh (tùy chọn)',
                    '✓ Website mẫu (tùy chọn)'
                ]
            },
            advertising: {
                label: 'Chạy quảng cáo',
                fields: [
                    '✓ Nền tảng: Facebook, TikTok, Google, YouTube',
                    '✓ Link Fanpage/Tài khoản (bắt buộc)',
                    '✓ Ngân sách dự kiến (tùy chọn)'
                ]
            },
            seo: {
                label: 'Dịch vụ SEO',
                fields: [
                    '✓ Website URL (bắt buộc)',
                    '✓ Từ khóa mục tiêu (bắt buộc)'
                ]
            }
        };

        $(function() {
            // Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            // Custom file input
            bsCustomFileInput.init();

            // Service type change handler
            function updateFieldsPreview(value) {
                const preview = $('#fields_preview');
                const list = $('#fields_list');

                if (value && serviceFields[value]) {
                    let html = '<ul class="mb-0">';
                    serviceFields[value].fields.forEach(field => {
                        html += '<li><small>' + field + '</small></li>';
                    });
                    html += '</ul>';

                    list.html(html);
                    preview.slideDown();
                } else {
                    preview.slideUp();
                }
            }

            // Service type change event
            $('#service_type').on('change', function() {
                updateFieldsPreview($(this).val());
            });

            // Trigger on page load to show current fields
            updateFieldsPreview($('#service_type').val());

            // Auto-generate slug
            let slugManuallyEdited = false;

            $('#slug').on('input', function() {
                slugManuallyEdited = true;
            });

            $('#name').on('input', function() {
                if (!slugManuallyEdited && !$('#slug').val()) {
                    var name = $(this).val();
                    var slug = name.toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
                        .replace(/đ/g, 'd')
                        .replace(/Đ/g, 'd')
                        .replace(/[^a-z0-9\s-]/g, '') // Remove special chars
                        .replace(/\s+/g, '-') // Replace spaces with -
                        .replace(/-+/g, '-') // Replace multiple - with single -
                        .replace(/^-+/, '') // Trim - from start
                        .replace(/-+$/, ''); // Trim - from end

                    $('#slug').val(slug);
                }
            });

            // Delete confirmation
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                if (confirm(
                        'Bạn có chắc chắn muốn xóa danh mục "{{ $category->name }}"?\n\nHành động này không thể khôi phục!'
                        )) {
                    $('#deleteForm').submit();
                }
            });
        });
    </script>
@endpush

@push('css')
    <style>
        #fields_preview {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        #fields_preview ul {
            padding-left: 20px;
        }

        #fields_preview li {
            margin-bottom: 3px;
        }

        .badge {
            font-size: 12px;
            padding: 4px 8px;
        }

        .list-group-item {
            padding: 0.5rem 1rem;
        }
    </style>
@endpush
