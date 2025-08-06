{{-- resources/views/source/web/wallet/components/styles/main.blade.php --}}
@push('header_css')
<style>

    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

.blink {
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.3; }
}

.fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.alert {
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== LAYOUT & SPACING ===== */
.btn-group .btn {
    margin: 2px;
}

.btn-group.flex-wrap {
    flex-wrap: wrap;
    gap: 5px;
}

.table td {
    padding: 0.5rem 0.75rem;
    border: none;
    vertical-align: middle;
}

/* ===== COMPONENTS ===== */
.badge {
    font-size: 0.85em;
    transition: all 0.3s ease;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.toast-container {
    z-index: 9999;
}

/* ===== HOVER EFFECTS ===== */
.img-fluid:hover,
.shadow-hover:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.btn-outline-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin: 2px 0;
        width: 100%;
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
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .btn-sm {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    
    .success-icon i {
        font-size: 3rem !important;
    }

    /* Mobile payment info adjustments */
    .table td {
        padding: 0.3rem 0.5rem;
        font-size: 0.85rem;
    }

    .badge {
        font-size: 0.7rem;
    }
}

/* ===== PAYMENT METHOD SPECIFIC ===== */
.crypto-address {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    background-color: #f8f9fa;
    padding: 0.5rem;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.payment-qr {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.payment-qr:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
}

/* ===== ACCESSIBILITY ===== */
.btn:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

.alert:focus-within {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

@media (prefers-contrast: high) {
    .btn-outline-primary {
        border-width: 2px;
    }
    
    .badge {
        border: 1px solid #000;
    }
    
    .card {
        border-width: 2px;
    }
}

/* ===== PRINT STYLES ===== */
@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .no-print,
    .btn-group,
    .alert-warning,
    .alert-primary,
    #status-update-alert,
    .countdown-timer {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    
    .card-header {
        background-color: #28a745 !important;
        color: white !important;
    }
    
    .badge {
        border: 1px solid #000;
        padding: 2px 4px;
    }

    .table {
        page-break-inside: avoid;
    }
}

/* ===== DARK MODE SUPPORT ===== */
@media (prefers-color-scheme: dark) {
    .card {
        background-color: #2d3748;
        border-color: #4a5568;
    }
    
    .card-body {
        color: #e2e8f0;
    }
    
    .table td {
        color: #e2e8f0;
    }

    .crypto-address {
        background-color: #4a5568;
        color: #e2e8f0;
        border-color: #718096;
    }
}

/* ===== CUSTOM SCROLLBAR ===== */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* ===== UTILITIES ===== */
.loading {
    pointer-events: none;
    opacity: 0.6;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.shadow-hover:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transition: box-shadow 0.15s ease-in-out;
}

/* ===== STATUS INDICATORS ===== */
.status-pending {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.status-success {
    animation: checkmark 0.5s ease-in-out;
}

@keyframes checkmark {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* ===== ENHANCED INTERACTION ===== */
.copy-button {
    transition: all 0.2s ease;
}

.copy-button:hover {
    background-color: #0056b3;
    transform: scale(1.05);
}

.copy-button:active {
    transform: scale(0.95);
}

/* ===== LOADING STATES ===== */
.btn.loading {
    position: relative;
    color: transparent;
}

.btn.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
}
</style>
@endpush