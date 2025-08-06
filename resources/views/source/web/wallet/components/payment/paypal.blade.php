<div class="row">
    <div class="col-md-8">
        <h6 class="text-primary mb-3">
            <i class="fab fa-paypal text-primary me-2"></i>PayPal Payment Information:
        </h6>
        <div class="table-responsive">
            <table class="table table-borderless">
                <tr>
                    <td><strong>PayPal Email:</strong></td>
                    <td>
                        <span class="badge bg-primary">{{ $paymentInfo['paypal_email'] ?? 'N/A' }}</span>
                        @if (isset($paymentInfo['paypal_email']))
                            <button class="btn btn-sm btn-outline-primary ms-2" 
                                    onclick="copyToClipboard('{{ $paymentInfo['paypal_email'] }}')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>Amount:</strong></td>
                    <td>
                        <span class="badge bg-success">${{ number_format($depositData['amount'], 2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Reference:</strong></td>
                    <td>
                        <span class="badge bg-warning text-dark">{{ $depositData['note_format'] ?? 'N/A' }}</span>
                        @if (isset($depositData['note_format']))
                            <button class="btn btn-sm btn-outline-primary ms-2" 
                                    onclick="copyToClipboard('{{ $depositData['note_format'] }}')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        <div class="alert alert-info">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                Send money to the PayPal email above with the reference code in the description.
            </small>
        </div>
    </div>
    <div class="col-md-4 text-center">
        <div class="border rounded p-3">
            <i class="fab fa-paypal fa-4x text-primary mb-3"></i>
            <h6>Send via PayPal</h6>
            <p class="small text-muted">Use PayPal app or website to send payment</p>
            <a href="https://www.paypal.com/send" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-external-link-alt me-1"></i>Open PayPal
            </a>
        </div>
    </div>
</div>