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
                                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Sản phẩm</a></li>
                                <li class="breadcrumb-item active">Chỉnh sửa sản phẩm</li>
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

                <div class="col-md-9">
                    <!-- Form chỉnh sửa sản phẩm -->
                    <form action="{{ route('admin.products.update', $product->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Thông tin cơ bản</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="name">Tên sản phẩm <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $product->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                        id="slug" name="slug" value="{{ old('slug', $product->slug) }}"
                                        placeholder="Tự động tạo nếu để trống">
                                    @error('slug')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="sku">Mã sản phẩm (SKU)</label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                        id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
                                    @error('sku')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="short_description">Mô tả ngắn</label>
                                    <textarea class="form-control @error('short_description') is-invalid @enderror" id="short_description"
                                        name="short_description" rows="3">{{ old('short_description', $product->short_description) }}</textarea>
                                    @error('short_description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">Mô tả chi tiết</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                        rows="5">{{ old('description', $product->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Giá & Kho hàng</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning mb-3 py-2" style="font-size: 13px;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Chính sách giá:</strong>
                                    Giá mới <u>áp dụng cho cả khách MỚI lẫn khách CŨ khi gia hạn</u>
                                    (phản ánh chi phí vận hành/lạm phát).
                                    Khách hàng đã mua sẽ thấy giá hiện tại tại trang gia hạn — kèm cảnh báo
                                    "đã điều chỉnh từ giá lần trước" để minh bạch.
                                    Hãy cân nhắc thông báo trước cho khách trước khi tăng mạnh.
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="price">Giá bán <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">VNĐ</span>
                                                </div>
                                                <input type="number"
                                                    class="form-control @error('price') is-invalid @enderror" id="price"
                                                    name="price" value="{{ old('price', $product->price) }}"
                                                    min="0" step="1000" required>
                                                @error('price')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sale_price">Giá khuyến mãi</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">VNĐ</span>
                                                </div>
                                                <input type="number"
                                                    class="form-control @error('sale_price') is-invalid @enderror"
                                                    id="sale_price" name="sale_price"
                                                    value="{{ old('sale_price', $product->sale_price) }}" min="0"
                                                    step="1000">
                                                @error('sale_price')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="stock">Số lượng trong kho</label>
                                    <input type="number" class="form-control @error('stock') is-invalid @enderror"
                                        id="stock" name="stock" value="{{ old('stock', $product->stock) }}"
                                        min="-1">
                                    <small class="form-text text-muted">Nhập -1 nếu không giới hạn số lượng</small>
                                    @error('stock')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">Dịch vụ định kỳ</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_recurring"
                                            name="is_recurring" value="1"
                                            {{ old('is_recurring', $product->is_recurring) ? 'checked' : '' }}
                                            onchange="toggleRecurringOptions()">
                                        <label class="custom-control-label" for="is_recurring">Đây là dịch vụ định
                                            kỳ</label>
                                    </div>
                                </div>

                                <div id="recurring_options"
                                    style="display: {{ old('is_recurring', $product->is_recurring) ? 'block' : 'none' }};">
                                    <div class="form-group">
                                        <label for="recurring_period">Chu kỳ (tháng)</label>
                                        <input type="number"
                                            class="form-control @error('recurring_period') is-invalid @enderror"
                                            id="recurring_period" name="recurring_period"
                                            value="{{ old('recurring_period', $product->recurring_period) }}"
                                            min="1">
                                        @error('recurring_period')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="auto_renew"
                                                name="auto_renew" value="1"
                                                {{ old('auto_renew', $product->auto_renew) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="auto_renew">Tự động gia hạn</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Dữ liệu bổ sung</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="meta_data">Dữ liệu Meta</label>
                                    <textarea class="form-control @error('meta_data') is-invalid @enderror" id="meta_data" name="meta_data"
                                        rows="3">{{ old('meta_data', is_array($product->meta_data) ? json_encode($product->meta_data, JSON_PRETTY_PRINT) : $product->meta_data) }}</textarea>
                                    <small class="form-text text-muted">Nhập dữ liệu meta (có thể chứa các khóa private,
                                        chứng chỉ SSL...)</small>
                                    @error('meta_data')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="options">Tùy chọn</label>
                                    <textarea class="form-control @error('options') is-invalid @enderror" id="options" name="options" rows="3">{{ old('options', is_array($product->options) ? json_encode($product->options, JSON_PRETTY_PRINT) : $product->options) }}</textarea>
                                    <small class="form-text text-muted">Nhập các tùy chọn bổ sung</small>
                                    @error('options')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">Phân loại & Trạng thái</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="category_id">Danh mục</label>
                                <select class="form-control select2bs4 @error('category_id') is-invalid @enderror"
                                    id="category_id" name="category_id" style="width: 100%;">
                                    <option value="">-- Chọn danh mục --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="parent_product_id">Sản phẩm cha</label>
                                <select class="form-control select2bs4 @error('parent_product_id') is-invalid @enderror"
                                    id="parent_product_id" name="parent_product_id" style="width: 100%;">
                                    <option value="">-- Không có --</option>
                                    @foreach ($parentProducts as $parentProduct)
                                        <option value="{{ $parentProduct->id }}"
                                            {{ old('parent_product_id', $product->parent_product_id) == $parentProduct->id ? 'selected' : '' }}>
                                            {{ $parentProduct->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Chọn nếu đây là biến thể của một sản phẩm khác</small>
                                @error('parent_product_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="type">Loại sản phẩm <span class="text-danger">*</span></label>
                                <select class="form-control @error('type') is-invalid @enderror" id="type"
                                    name="type" required>
                                    <option value="product"
                                        {{ old('type', $product->type) == 'product' ? 'selected' : '' }}>Sản phẩm</option>
                                    <option value="service"
                                        {{ old('type', $product->type) == 'service' ? 'selected' : '' }}>Dịch vụ</option>
                                    <option value="ssl" {{ old('type', $product->type) == 'ssl' ? 'selected' : '' }}>
                                        SSL</option>
                                    <option value="domain"
                                        {{ old('type', $product->type) == 'domain' ? 'selected' : '' }}>Tên miền</option>
                                    <option value="hosting"
                                        {{ old('type', $product->type) == 'hosting' ? 'selected' : '' }}>Hosting</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="product_status">Trạng thái</label>
                                <select class="form-control @error('product_status') is-invalid @enderror"
                                    id="product_status" name="product_status">
                                    <option value="active"
                                        {{ old('product_status', $product->product_status) == 'active' ? 'selected' : '' }}>
                                        Kích hoạt</option>
                                    <option value="inactive"
                                        {{ old('product_status', $product->product_status) == 'inactive' ? 'selected' : '' }}>
                                        Vô hiệu</option>
                                    <option value="draft"
                                        {{ old('product_status', $product->product_status) == 'draft' ? 'selected' : '' }}>
                                        Bản nháp</option>
                                </select>
                                @error('product_status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="sort_order">Thứ tự hiển thị</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                    id="sort_order" name="sort_order"
                                    value="{{ old('sort_order', $product->sort_order) }}" min="0">
                                @error('sort_order')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_featured"
                                        name="is_featured" value="1"
                                        {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_featured">Sản phẩm nổi bật</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Hình ảnh</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="image">Hình ảnh chính</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file"
                                            class="custom-file-input @error('image') is-invalid @enderror" id="image"
                                            name="image">
                                        <label class="custom-file-label" for="image">Chọn file</label>
                                    </div>
                                </div>
                                @error('image')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            @if ($product->image)
                                <div class="mt-3 text-center">
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                        class="img-fluid img-thumbnail" style="max-height: 200px;">
                                    <p class="text-muted small mt-2">Hình ảnh hiện tại. Tải lên hình mới nếu muốn thay thế.
                                    </p>
                                </div>
                            @endif

                            <div class="preview-image mt-3 text-center" style="display: none;">
                                <img id="image-preview" src="#" alt="Xem trước hình ảnh"
                                    class="img-fluid img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('admin.products.update', $product->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary btn-block">Cập nhật sản phẩm</button>
                                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-block">Hủy</a>
                            </div>
                        </div>
                    </form>
                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title">Vùng nguy hiểm</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-danger">Cảnh báo: Các hành động dưới đây không thể khôi phục!</p>

                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                id="deleteForm">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-block" onclick="confirmDelete()">
                                    <i class="fas fa-trash mr-2"></i>Xóa sản phẩm này
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script>
        $(function() {
            // Select2
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            });

            // Custom file input
            bsCustomFileInput.init();

            // Tự động tạo slug từ tên
            $('#name').on('input', function() {
                var name = $(this).val();
                var slug = name.toLowerCase()
                    .replace(/\s+/g, '-') // Thay thế khoảng trắng bằng -
                    .replace(/[^\w\-]+/g, '') // Loại bỏ tất cả các ký tự không phải chữ
                    .replace(/\-\-+/g, '-') // Thay thế nhiều - bằng một -
                    .replace(/^-+/, '') // Cắt - từ đầu văn bản
                    .replace(/-+$/, ''); // Cắt - từ cuối văn bản

                $('#slug').val(slug);
            });

            // Xem trước hình ảnh
            $("#image").change(function() {
                readURL(this);
            });

            // CKEditor cho mô tả chi tiết
            if (typeof CKEDITOR !== 'undefined') {
                CKEDITOR.replace('description');
            }
        });

        // Hàm xem trước hình ảnh
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#image-preview').attr('src', e.target.result);
                    $('.preview-image').show();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Hàm hiển thị/ẩn tùy chọn định kỳ
        function toggleRecurringOptions() {
            if ($('#is_recurring').is(':checked')) {
                $('#recurring_options').show();
            } else {
                $('#recurring_options').hide();
            }
        }

        // Xác nhận xóa
        function confirmDelete() {
            if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này? Hành động này không thể khôi phục!')) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
    @push('js')
        <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
        <script>
            $(function() {
                // Các code khác...

                // CKEditor cho mô tả chi tiết - không kèm upload ảnh
                CKEDITOR.replace('description');
            });
        </script>
    @endpush
@endpush
