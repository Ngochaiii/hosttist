<div class="text-center mt-4">
    <div class="btn-group flex-wrap" role="group">
        <a href="{{ route('customer.profile') ?? '#' }}" class="btn btn-primary">
            <i class="fas fa-user"></i> Về trang tài khoản
        </a>
        <a href="{{ route('deposit') }}" class="btn btn-outline-primary">
            <i class="fas fa-plus"></i> Tạo yêu cầu nạp tiền khác
        </a>
        <button class="btn btn-outline-success" onclick="window.print()">
            <i class="fas fa-print"></i> In thông tin
        </button>
        <button class="btn btn-outline-info" onclick="checkStatus()">
            <i class="fas fa-sync-alt"></i> Kiểm tra trạng thái
        </button>
    </div>
</div>