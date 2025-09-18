@extends('layouts.admin.index')

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-5">
                    <!-- Thêm/Sửa Danh Mục -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">{{ isset($category) ? 'Chỉnh sửa danh mục' : 'Thêm danh mục mới' }}</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <!-- form start -->
                        <form id="categoryForm"
                            action="{{ isset($category) ? route('admin.categories.update', $category->id) : route('admin.categories.store') }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            @if (isset($category))
                                @method('PUT')
                            @endif

                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" placeholder="Nhập tên danh mục"
                                        value="{{ old('name', $category->name ?? '') }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                        id="slug" name="slug" placeholder="Tự động tạo nếu để trống"
                                        value="{{ old('slug', $category->slug ?? '') }}">
                                    @error('slug')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="parent_id">Danh mục cha</label>
                                    <select class="form-control select2bs4 @error('parent_id') is-invalid @enderror"
                                        id="parent_id" name="parent_id" style="width: 100%;">
                                        <option value="">-- Không có --</option>
                                        @foreach ($parentCategories as $parentCategory)
                                            @if (!isset($category) || $parentCategory->id != $category->id)
                                                <option value="{{ $parentCategory->id }}"
                                                    {{ old('parent_id', $category->parent_id ?? '') == $parentCategory->id ? 'selected' : '' }}>
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
                                        <small class="text-muted">(Tùy chọn)</small>
                                    </label>
                                    <select class="form-control @error('service_type') is-invalid @enderror"
                                        id="service_type" name="service_type">
                                        <option value="">-- Không yêu cầu thông tin đặc biệt --</option>
                                        @php
                                            $currentType =
                                                isset($category) && $category->meta_data
                                                    ? json_decode($category->meta_data, true)['service_type'] ?? ''
                                                    : '';
                                        @endphp
                                        <option value="ssl"
                                            {{ old('service_type', $currentType) == 'ssl' ? 'selected' : '' }}>
                                            🔒 SSL Certificate (yêu cầu domain)
                                        </option>
                                        <option value="vps"
                                            {{ old('service_type', $currentType) == 'vps' ? 'selected' : '' }}>
                                            💻 VPS/Cloud Server (yêu cầu username, OS)
                                        </option>
                                        <option value="domain"
                                            {{ old('service_type', $currentType) == 'domain' ? 'selected' : '' }}>
                                            🌐 Tên miền (yêu cầu domain)
                                        </option>
                                        <option value="hosting"
                                            {{ old('service_type', $currentType) == 'hosting' ? 'selected' : '' }}>
                                            🗄️ Web Hosting (domain tùy chọn)
                                        </option>
                                        <option value="email"
                                            {{ old('service_type', $currentType) == 'email' ? 'selected' : '' }}>
                                            📧 Email doanh nghiệp (yêu cầu domain, số lượng)
                                        </option>
                                        <option value="web_design"
                                            {{ old('service_type', $currentType) == 'web_design' ? 'selected' : '' }}>
                                            🎨 Thiết kế website (yêu cầu SĐT)
                                        </option>
                                        <option value="advertising"
                                            {{ old('service_type', $currentType) == 'advertising' ? 'selected' : '' }}>
                                            📢 Chạy quảng cáo (yêu cầu link FB/TikTok)
                                        </option>
                                        <option value="seo"
                                            {{ old('service_type', $currentType) == 'seo' ? 'selected' : '' }}>
                                            🔍 Dịch vụ SEO (yêu cầu website URL, keywords)
                                        </option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Chọn loại dịch vụ để hệ thống tự động yêu cầu thông tin phù hợp từ khách hàng
                                    </small>
                                    @error('service_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Preview các fields sẽ yêu cầu -->
                                <div id="fields_preview" class="alert alert-info" style="display: none;">
                                    <h6><i class="fas fa-info-circle"></i> Thông tin sẽ yêu cầu từ khách hàng:</h6>
                                    <div id="fields_list"></div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Mô tả</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                        rows="3" placeholder="Nhập mô tả danh mục">{{ old('description', $category->description ?? '') }}</textarea>
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
                                    @if (isset($category) && $category->image)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $category->image) }}"
                                                alt="{{ $category->name }}" class="img-thumbnail"
                                                style="max-height: 100px">
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label for="sort_order">Thứ tự hiển thị</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                        id="sort_order" name="sort_order"
                                        value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                                    @error('sort_order')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="status"
                                            name="status" value="active"
                                            {{ old('status', $category->status ?? 'active') == 'active' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="status">Kích hoạt</label>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> {{ isset($category) ? 'Cập nhật' : 'Thêm mới' }}
                                </button>
                                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                                @if (isset($category))
                                    <a href="{{ route('admin.categories.create') }}" class="btn btn-success float-right">
                                        <i class="fas fa-plus"></i> Thêm mới
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                    <!-- /.card -->
                </div>

                <div class="col-md-7">
                    <!-- Danh sách Danh Mục -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách danh mục sản phẩm</h3>
                            <div class="card-tools">
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <input type="text" name="table_search" class="form-control float-right"
                                        placeholder="Tìm kiếm..." id="searchInput">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-default">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">ID</th>
                                        <th>Tên danh mục</th>
                                        <th>Loại dịch vụ</th>
                                        <th>Danh mục cha</th>
                                        <th style="width: 100px">Thứ tự</th>
                                        <th style="width: 100px">Trạng thái</th>
                                        <th style="width: 120px">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $cat)
                                        <tr>
                                            <td>{{ $cat->id }}</td>
                                            <td>
                                                @if ($cat->image)
                                                    <img src="{{ asset('storage/' . $cat->image) }}"
                                                        alt="{{ $cat->name }}" class="img-circle mr-2"
                                                        style="max-height: 30px">
                                                @endif
                                                <strong>{{ $cat->name }}</strong>
                                            </td>
                                            <td>
                                                @php
                                                    $metaData = $cat->meta_data
                                                        ? json_decode($cat->meta_data, true)
                                                        : null;
                                                    $serviceType = $metaData['service_type'] ?? null;
                                                    $serviceLabels = [
                                                        'ssl' => ['SSL', 'warning'],
                                                        'vps' => ['VPS', 'info'],
                                                        'domain' => ['Domain', 'success'],
                                                        'hosting' => ['Hosting', 'primary'],
                                                        'email' => ['Email', 'secondary'],
                                                        'web_design' => ['Thiết kế', 'danger'],
                                                        'advertising' => ['Quảng cáo', 'warning'],
                                                        'seo' => ['SEO', 'dark'],
                                                    ];
                                                @endphp
                                                @if ($serviceType && isset($serviceLabels[$serviceType]))
                                                    <span class="badge badge-{{ $serviceLabels[$serviceType][1] }}">
                                                        {{ $serviceLabels[$serviceType][0] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $cat->parent ? $cat->parent->name : '-' }}</td>
                                            <td>{{ $cat->sort_order }}</td>
                                            <td>
                                                <span
                                                    class="badge {{ $cat->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                                    {{ $cat->status == 'active' ? 'Kích hoạt' : 'Vô hiệu' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.categories.edit', $cat->id) }}"
                                                    class="btn btn-sm btn-info" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.categories.destroy', $cat->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger delete-btn"
                                                        data-name="{{ $cat->name }}" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                        <div class="card-footer clearfix">
                            {{ $categories->links() }}
                        </div>
                    </div>
                    <!-- /.card -->
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

            // Auto-generate slug from name
            $('#name').on('input', function() {
                if (!$('#slug').val() || $('#slug').data('auto-generated')) {
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

                    $('#slug').val(slug).data('auto-generated', true);
                }
            });

            // Manual slug editing
            $('#slug').on('input', function() {
                $(this).data('auto-generated', false);
            });

            // Service type change handler
            $('#service_type').on('change', function() {
                const value = $(this).val();
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
            });

            // Trigger on page load if editing
            $('#service_type').trigger('change');

            // Delete confirmation
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const name = $(this).data('name');

                if (confirm('Bạn có chắc muốn xóa danh mục "' + name + '"?')) {
                    form.submit();
                }
            });

            // Simple search
            $('#searchInput').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
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
            font-size: 11px;
            padding: 3px 8px;
        }
    </style>
@endpush
