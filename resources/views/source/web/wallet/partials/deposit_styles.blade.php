{{-- resources/views/source/web/wallet/partials/deposit_styles.blade.php --}}
@push('header_css')
<style>
/* Language switcher */
.btn-group .btn.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

/* Gift icon animation */
.gift-icon {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

/* Amount buttons */
.quick-amounts .btn-check:checked + .amount-btn {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
    transform: scale(1.05);
}

.amount-btn {
    position: relative;
    height: 80px;
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.amount-btn:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.amount-label {
    font-weight: bold;
    font-size: 0.9rem;
}

.amount-value {
    font-size: 0.8rem;
    color: #6c757d;
}

.bonus-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #28a745;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Payment method cards */
.payment-method-card {
    height: 100%;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.payment-method-label {
    display: flex;
    align-items: center;
    padding: 1.5rem 1rem;
    height: 100%;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    position: relative;
}

.payment-method-label:hover {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn-check:checked + .payment-method-label {
    background-color: #e7f3ff;
    border-color: #0d6efd;
    color: #0d6efd;
}

.payment-icon {
    font-size: 2rem;
    margin-right: 1rem;
    color: #0d6efd;
}

.payment-text {
    flex: 1;
}

.payment-text strong {
    display: block;
    margin-bottom: 0.25rem;
}

.payment-text small {
    color: #6c757d;
}

.payment-badge {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.btn-check:checked + .payment-method-label .payment-badge {
    opacity: 1;
}

/* Payment info styling */
.bank-info-grid {
    display: grid;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #495057;
    min-width: 100px;
}

.info-value {
    display: flex;
    align-items: center;
    flex: 1;
    justify-content: flex-end;
}

.info-value code {
    background: #f8f9fa;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.9rem;
}

/* Crypto specific styling */
.crypto-header {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.crypto-wallet {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-top: 1rem;
}

.crypto-wallet code {
    background: #343a40;
    color: #fff;
    padding: 0.5rem;
    border-radius: 0.25rem;
    word-break: break-all;
    font-size: 0.85rem;
}

/* Preview styling */
#amount-preview {
    border-left: 4px solid #0d6efd;
    background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%);
}

#amount-preview .row > div {
    padding: 0.5rem;
    text-align: center;
}

/* Submit button */
.submit-btn {
    height: 60px;
    font-size: 1.1rem;
    font-weight: bold;
    position: relative;
    overflow: hidden;
}

.submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.submit-btn:hover::before {
    left: 100%;
}

/* Responsive design */
@media (max-width: 768px) {
    .quick-amounts {
        flex-direction: column;
    }
    
    .quick-amounts .btn {
        margin-bottom: 0.5rem;
    }
    
    .amount-btn {
        height: 60px;
    }
    
    .payment-method-label {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
    }
    
    .payment-icon {
        font-size: 1.5rem;
        margin-right: 0;
        margin-bottom: 0.5rem;
    }
    
    .bank-info-grid {
        font-size: 0.9rem;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .info-value {
        justify-content: flex-start;
        margin-top: 0.25rem;
        width: 100%;
    }
    
    .crypto-wallet code {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>
@endpush