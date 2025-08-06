@push('footer_js')
<script>
// Status checking functionality
class DepositStatusChecker {
    constructor(transactionCode) {
        this.transactionCode = transactionCode;
        this.statusBadge = document.getElementById('status-badge');
        this.checkButton = document.querySelector('button[onclick="checkStatus()"]');
    }

    async check() {
        if (!this.transactionCode) return;

        this.setLoading(true);

        try {
            const response = await fetch(`{{ route('deposit.status', '') }}/${this.transactionCode}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                this.updateStatus(data.data);
                this.showStatusToast(data.data);
                
                if (data.data.status === 'approved') {
                    this.showCelebration();
                }
            } else {
                ToastManager.show('Không thể kiểm tra trạng thái: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Status check error:', error);
            ToastManager.show('Có lỗi xảy ra khi kiểm tra trạng thái', 'error');
        } finally {
            this.setLoading(false);
        }
    }

    updateStatus(statusData) {
        if (this.statusBadge) {
            const { status_color, status_text, status, is_expired } = statusData;
            
            this.statusBadge.className = `badge bg-${status_color}`;
            this.statusBadge.innerHTML = `<i class="fas fa-${this.getStatusIcon(status)}"></i> ${status_text}`;

            if (is_expired && status === 'pending') {
                this.statusBadge.className = 'badge bg-secondary';
                this.statusBadge.innerHTML = '<i class="fas fa-clock"></i> Đã hết hạn';
            }
        }
    }

    showStatusToast(statusData) {
        const toastType = statusData.status === 'approved' ? 'success' : 'info';
        ToastManager.show(`Trạng thái: ${statusData.status_text}`, toastType);
    }

    showCelebration() {
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <h5><i class="fas fa-party-horn"></i> Chúc mừng!</h5>
                <p>Giao dịch của bạn đã được duyệt thành công. Số dư tài khoản đã được cập nhật!</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.querySelector('.card-body').insertAdjacentHTML('afterbegin', alertHtml);
    }

    setLoading(isLoading) {
        if (this.checkButton) {
            if (isLoading) {
                this.checkButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
                this.checkButton.disabled = true;
            } else {
                this.checkButton.innerHTML = '<i class="fas fa-sync-alt"></i> Kiểm tra trạng thái';
                this.checkButton.disabled = false;
            }
        }
    }

    getStatusIcon(status) {
        const icons = {
            'pending': 'clock',
            'approved': 'check-circle',
            'rejected': 'times-circle'
        };
        return icons[status] || 'question-circle';
    }

    // Auto check every 30 seconds
    startAutoCheck() {
        const interval = setInterval(() => {
            if (document.getElementById('countdown')?.textContent !== 'Đã hết thời gian') {
                this.check();
            } else {
                clearInterval(interval);
            }
        }, 30000);
    }
}

// Initialize status checker
const statusChecker = new DepositStatusChecker('{{ $depositData['transaction_code'] ?? '' }}');

// Global function for backward compatibility
function checkStatus() {
    statusChecker.check();
}

// Auto-start status checking
document.addEventListener('DOMContentLoaded', () => {
    statusChecker.startAutoCheck();
    document.getElementById('status-update-alert')?.classList.remove('d-none');
});
</script>
@endpush