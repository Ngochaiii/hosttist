  <!-- jQery -->
  <script src="{{asset('assets/web/hostit/js/jquery-3.4.1.min.js')}}"></script>
  <!-- bootstrap js -->
  <script src="{{asset('assets/web/hostit/js/bootstrap.js')}}"></script>
  <!-- custom js -->
  <script src="{{asset('assets/web/hostit/js/custom.js')}}"></script>
  <script>
// Tự động ẩn thông báo sau 5 giây.
// CHỈ áp cho .alert-dismissible (flash message / lỗi validate). Trước đây chọn mọi
// .alert nên nội dung tĩnh cũng biến mất sau 5 giây: mô tả dịch vụ, cảnh báo sắp
// hết hạn, ghi chú bảo mật, thông báo "chưa có đơn hàng nào"...
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(function(alert) {
        setTimeout(function() {
            $(alert).alert('close');
        }, 5000);
    });
});
</script>
<!-- Thêm SweetAlert2 vào layout chính của bạn -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra nếu có thông báo thành công từ session
    @if(session('success'))
        Swal.fire({
            title: 'Thành công!',
            text: "{{ session('success') }}",
            icon: 'success',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
    @endif

    // Hiển thị lỗi nếu có
    @if(session('error'))
        Swal.fire({
            title: 'Lưu ý!',
            text: "{{ session('error') }}",
            icon: 'warning',
            confirmButtonText: 'Đã hiểu'
        });
    @endif
});
</script>
