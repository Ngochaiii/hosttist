{{-- resources/views/source/web/wallet/partials/terms_submit.blade.php --}}
<!-- Terms and Conditions -->
<div class="mb-4">
    <div class="form-check form-check-lg">
        <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
        <label class="form-check-label" for="agree_terms">
            <i class="fas fa-shield-alt text-success"></i>
            @if($locale === 'vi')
                Tôi đã đọc và đồng ý với các điều khoản nạp tiền và chính sách bảo mật
            @else
                I have read and agree to the deposit terms and privacy policy
            @endif
        </label>
    </div>
</div>

<!-- Submit Button -->
<div class="d-grid">
    <button type="submit" class="btn btn-primary btn-lg submit-btn" id="submitBtn">
        <i class="fas fa-credit-card"></i>
        <span class="submit-text">
            {{ $locale === 'vi' ? 'Tiến hành nạp tiền' : 'Proceed to Deposit' }}
        </span>
        <div class="spinner-border spinner-border-sm d-none" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </button>
</div>