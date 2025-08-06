@push('footer_js')
<script>
// Countdown timer functionality
class CountdownTimer {
    constructor(durationMinutes = 30) {
        this.timeLeft = durationMinutes * 60; // Convert to seconds
        this.countdownElement = document.getElementById('countdown');
        this.timer = null;
    }

    start() {
        if (!this.countdownElement) return;

        this.update();
        this.timer = setInterval(() => this.update(), 1000);
    }

    update() {
        const minutes = Math.floor(this.timeLeft / 60);
        const seconds = this.timeLeft % 60;

        this.countdownElement.textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        this.updateStyles();
        this.checkWarnings();

        if (this.timeLeft <= 0) {
            this.expire();
            return;
        }

        this.timeLeft--;
    }

    updateStyles() {
        if (this.timeLeft <= 0) {
            this.countdownElement.textContent = 'Đã hết thời gian';
            this.countdownElement.className = 'ms-2 text-danger';
        } else if (this.timeLeft <= 300) { // 5 minutes
            this.countdownElement.className = 'ms-2 text-danger blink';
        } else if (this.timeLeft <= 600) { // 10 minutes
            this.countdownElement.className = 'ms-2 text-warning';
        }
    }

    checkWarnings() {
        if (this.timeLeft === 300) { // 5 minutes warning
            ToastManager.show('Chỉ còn 5 phút! Vui lòng hoàn tất thanh toán.', 'error');
        }
    }

    expire() {
        clearInterval(this.timer);
        
        ToastManager.show(
            'Thông tin giao dịch đã hết hiệu lực. Vui lòng tạo yêu cầu mới nếu chưa thanh toán.',
            'error'
        );

        // Update status badge if still pending
        const statusBadge = document.getElementById('status-badge');
        if (statusBadge?.textContent.includes('Chờ thanh toán')) {
            statusBadge.className = 'badge bg-secondary';
            statusBadge.innerHTML = '<i class="fas fa-clock"></i> Đã hết hạn';
        }
    }

    stop() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    }
}

// Initialize countdown
const countdown = new CountdownTimer(30);

document.addEventListener('DOMContentLoaded', () => {
    countdown.start();
});
</script>
@endpush