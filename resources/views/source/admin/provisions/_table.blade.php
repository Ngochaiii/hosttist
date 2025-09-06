{{-- resources/views/admin/provisions/_table.blade.php --}}
<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th style="width: 50px">ID</th>
                <th>Khách hàng</th>
                <th>Sản phẩm</th>
                <th>Loại</th>
                <th>Trạng thái</th>
                <th>Ưu tiên</th>
                <th>Ngày tạo</th>
                <th style="width: 120px">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($provisions as $provision)
            <tr class="@if($provision->priority >= 8) table-warning @endif">
                <td>
                    <strong>{{ $provision->id }}</strong>
                    @if($provision->external_id)
                        <br><small class="text-muted">{{ $provision->external_id }}</small>
                    @endif
                </td>
                <td>
                    <strong>{{ $provision->customer->name ?? 'N/A' }}</strong>
                    @if($provision->customer && $provision->customer->user)
                        <br><small class="text-muted">{{ $provision->customer->user->email }}</small>
                    @endif
                </td>
                <td>
                    <strong>{{ $provision->product->name ?? 'N/A' }}</strong>
                    @if($provision->orderItem)
                        <br><small class="text-info">Order: #{{ $provision->orderItem->id }}</small>
                    @endif
                </td>
                <td>
                    @switch($provision->provision_type)
                        @case('digital')
                            <span class="badge badge-primary">Kỹ thuật số</span>
                            @break
                        @case('physical')
                            <span class="badge badge-info">Vật lý</span>
                            @break
                        @case('service')
                            <span class="badge badge-success">Dịch vụ</span>
                            @break
                        @default
                            <span class="badge badge-secondary">{{ $provision->provision_type }}</span>
                    @endswitch
                </td>
                <td>
                    @switch($provision->provision_status)
                        @case('pending')
                            <span class="badge badge-warning">Đang chờ</span>
                            @break
                        @case('processing')
                            <span class="badge badge-info">Đang xử lý</span>
                            @break
                        @case('completed')
                            <span class="badge badge-success">Hoàn thành</span>
                            @if($provision->provisioned_at)
                                <br><small class="text-muted">{{ $provision->provisioned_at->format('d/m H:i') }}</small>
                            @endif
                            @break
                        @case('failed')
                            <span class="badge badge-danger">Thất bại</span>
                            @break
                        @case('cancelled')
                            <span class="badge badge-secondary">Đã hủy</span>
                            @break
                        @default
                            <span class="badge badge-dark">{{ $provision->provision_status }}</span>
                    @endswitch
                </td>
                <td class="text-center">
                    @if($provision->priority >= 8)
                        <span class="badge badge-danger">{{ $provision->priority }}</span>
                    @else
                        <span class="badge badge-info">{{ $provision->priority }}</span>
                    @endif
                </td>
                <td>
                    {{ $provision->created_at->format('d/m/Y H:i') }}
                    @if($provision->estimated_completion)
                        <br><small class="text-info">
                            Dự kiến: {{ $provision->estimated_completion->format('d/m H:i') }}
                        </small>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.provisions.show', $provision->id) }}" 
                       class="btn btn-sm btn-info" title="Xem">
                        <i class="fas fa-eye"></i>
                    </a>

                    @if($provision->isPending())
                        <button type="button" class="btn btn-sm btn-success" 
                                onclick="startProcessing({{ $provision->id }})" 
                                title="Bắt đầu">
                            <i class="fas fa-play"></i>
                        </button>
                    @endif

                    @if($provision->isPending() || $provision->isProcessing())
                        <a href="{{ route('admin.provisions.form', $provision->id) }}" 
                           class="btn btn-sm btn-primary" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endif

                    @if($provision->isProcessing())
                        <button type="button" class="btn btn-sm btn-success" 
                                onclick="completeProvision({{ $provision->id }})" 
                                title="Hoàn thành">
                            <i class="fas fa-check"></i>
                        </button>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">
                    <div class="py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Không có provision nào.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>