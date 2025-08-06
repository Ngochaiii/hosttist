@push('footer_js')
<script>
// Copy to clipboard functionality
class ClipboardManager {
    static copy(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(() => this.showSuccess(text))
                .catch(err => {
                    console.error('Clipboard API failed:', err);
                    this.fallbackCopy(text);
                });
        } else {
            this.fallbackCopy(text);
        }
    }

    static fallbackCopy(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.cssText = "position:fixed;top:0;left:0;opacity:0;";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                this.showSuccess(text);
            } else {
                this.showError('Không thể sao chép');
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            this.showError('Không thể sao chép');
        }

        document.body.removeChild(textArea);
    }

    static showSuccess(text) {
        ToastManager.show(`Đã sao chép: ${text}`, 'success');
    }

    static showError(message) {
        ToastManager.show(message, 'error');
    }
}

// Global function for backward compatibility
function copyToClipboard(text) {
    ClipboardManager.copy(text);
}
</script>
@endpush