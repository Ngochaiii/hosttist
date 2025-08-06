{{-- resources/views/source/web/wallet/partials/deposit_scripts.blade.php --}}
@push('footer_js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const locale = '{{ $locale }}';
            const usdRate = {{ $usdRate }};

            // Elements
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const paymentDetails = document.querySelectorAll('.payment-details');
            const amountPresets = document.querySelectorAll('input[name="amount_preset"]');
            const amountInput = document.getElementById('amount');
            const cryptoOptions = document.getElementById('crypto-options');
            const cryptoInputs = document.querySelectorAll('input[name="crypto_type"]');
            const cryptoWallets = document.querySelectorAll('.crypto-wallet');
            const submitBtn = document.getElementById('submitBtn');
            const depositForm = document.getElementById('depositForm');

            // Show payment details based on selected method
            function showPaymentDetails() {
                const selected = document.querySelector('input[name="payment_method"]:checked').value;

                // KHÔNG ẩn payment details nữa - để luôn hiển thị tất cả
                // paymentDetails.forEach(detail => detail.style.display = 'none');
                // document.getElementById(selected + '_info').style.display = 'block';

                // Chỉ xử lý crypto options
                if (selected === 'crypto') {
                    cryptoOptions.classList.remove('d-none');
                    if (!document.querySelector('input[name="crypto_type"]:checked')) {
                        document.getElementById('crypto_bitcoin').checked = true;
                        showCryptoWallet();
                    }
                } else {
                    cryptoOptions.classList.add('d-none');
                }
            }

            // Show crypto wallet info
            function showCryptoWallet() {
                const selected = document.querySelector('input[name="crypto_type"]:checked');
                if (selected) {
                    cryptoWallets.forEach(wallet => wallet.style.display = 'none');
                    const targetWallet = document.querySelector(`.crypto-wallet[data-crypto="${selected.value}"]`);
                    if (targetWallet) targetWallet.style.display = 'block';
                }
            }

            // Handle amount preset changes
            amountPresets.forEach(preset => {
                preset.addEventListener('change', function() {
                    if (this.value !== 'custom') {
                        amountInput.value = this.value;
                        calculatePreview();
                    }
                });
            });

            // Calculate and show preview
            function calculatePreview() {
                const amount = parseFloat(amountInput.value) || 0;

                if (amount <= 0) {
                    document.getElementById('amount-preview').classList.add('d-none');
                    return;
                }

                let amountVND, bonusVND, totalVND;

                // Convert to VND if needed
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
                if (['paypal', 'crypto'].includes(selectedMethod) && locale === 'en') {
                    amountVND = amount * usdRate;
                } else {
                    amountVND = amount;
                }

                // Calculate bonus (5% for deposits >= 10M VND)
                bonusVND = amountVND >= 10000000 ? Math.round(amountVND * 0.05) : 0;
                totalVND = amountVND + bonusVND;

                // Display in user's currency
                if (locale === 'vi') {
                    document.getElementById('preview-amount').textContent = formatVND(amount);
                    document.getElementById('preview-bonus').textContent = formatVND(bonusVND);
                    document.getElementById('preview-total').textContent = formatVND(totalVND);
                } else {
                    const bonusUSD = bonusVND / usdRate;
                    const totalUSD = totalVND / usdRate;
                    document.getElementById('preview-amount').textContent = '$' + formatUSD(amount);
                    document.getElementById('preview-bonus').textContent = '$' + formatUSD(bonusUSD);
                    document.getElementById('preview-total').textContent = '$' + formatUSD(totalUSD);
                }

                document.getElementById('amount-preview').classList.remove('d-none');
            }

            // Format functions
            function formatVND(num) {
                return new Intl.NumberFormat('vi-VN').format(num) + ' đ';
            }

            function formatUSD(num) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(num);
            }

            // Copy text function
            window.copyText = function(text) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast(
                        locale === 'vi' ? 'Đã sao chép: ' + text : 'Copied: ' + text,
                        'success'
                    );
                }).catch(() => {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.top = '0';
                    textarea.style.left = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);

                    showToast(
                        locale === 'vi' ? 'Đã sao chép: ' + text : 'Copied: ' + text,
                        'success'
                    );
                });
            };

            // Show toast notification
            function showToast(message, type = 'info') {
                // Simple alert for now, can be enhanced later
                alert(message);
            }

            // Form submission handling
            depositForm.addEventListener('submit', function(e) {
                const submitText = submitBtn.querySelector('.submit-text');
                const spinner = submitBtn.querySelector('.spinner-border');

                // Show loading state
                submitText.textContent = locale === 'vi' ? 'Đang xử lý...' : 'Processing...';
                spinner.classList.remove('d-none');
                submitBtn.disabled = true;

                // Reset after 10 seconds if something goes wrong
                setTimeout(() => {
                    submitText.textContent = locale === 'vi' ? 'Tiến hành nạp tiền' :
                        'Proceed to Deposit';
                    spinner.classList.add('d-none');
                    submitBtn.disabled = false;
                }, 10000);
            });

            // Event listeners
            paymentMethods.forEach(method => method.addEventListener('change', showPaymentDetails));
            cryptoInputs.forEach(input => input.addEventListener('change', showCryptoWallet));
            amountInput.addEventListener('input', calculatePreview);

            // Initialize
            showPaymentDetails();
            calculatePreview();
        });
        // Thêm vào deposit_scripts.blade.php

        // Custom validation function
        function validateAmount() {
            const amount = parseFloat(amountInput.value) || 0;
            const minAmount = locale === 'vi' ? {{ $minDeposit }} : {{ round($minDeposit / $usdRate, 2) }};
            const maxAmount = locale === 'vi' ? {{ $maxDeposit }} : {{ round($maxDeposit / $usdRate, 2) }};

            // Clear previous custom validity
            amountInput.setCustomValidity('');

            if (amount < minAmount) {
                const message = locale === 'vi' ?
                    `Số tiền tối thiểu là ${new Intl.NumberFormat('vi-VN').format(minAmount)} đ` :
                    `Minimum amount is $${minAmount}`;
                amountInput.setCustomValidity(message);
                return false;
            }

            if (amount > maxAmount) {
                const message = locale === 'vi' ?
                    `Số tiền tối đa là ${new Intl.NumberFormat('vi-VN').format(maxAmount)} đ` :
                    `Maximum amount is $${maxAmount}`;
                amountInput.setCustomValidity(message);
                return false;
            }

            return true;
        }

        // Update amount input event listener
        amountInput.addEventListener('input', function() {
            validateAmount();
            calculatePreview();
        });

        // Add form validation before submit
        depositForm.addEventListener('submit', function(e) {
            if (!validateAmount()) {
                e.preventDefault();
                amountInput.reportValidity();
                return false;
            }

            // Continue with loading state...
            const submitText = submitBtn.querySelector('.submit-text');
            const spinner = submitBtn.querySelector('.spinner-border');

            submitText.textContent = locale === 'vi' ? 'Đang xử lý...' : 'Processing...';
            spinner.classList.remove('d-none');
            submitBtn.disabled = true;

            setTimeout(() => {
                submitText.textContent = locale === 'vi' ? 'Tiến hành nạp tiền' : 'Proceed to Deposit';
                spinner.classList.add('d-none');
                submitBtn.disabled = false;
            }, 10000);
        });
    </script>
@endpush
