{{-- resources/views/source/web/wallet/components/scripts/force-display.blade.php --}}
@push('footer_js')
<script>
// Force display instructions - chống mọi thứ có thể ẩn nó
document.addEventListener('DOMContentLoaded', function() {
    function forceDisplayInstructions() {
        // Tìm tất cả các element instructions
        const instructionsElements = [
            document.getElementById('instructions-alert'),
            ...document.querySelectorAll('.alert-info'),
            ...document.querySelectorAll('[id*="instruction"]'),
            ...document.querySelectorAll('[class*="instruction"]')
        ];

        instructionsElements.forEach(element => {
            if (element) {
                // Force display styles
                element.style.display = 'block';
                element.style.visibility = 'visible';
                element.style.opacity = '1';
                element.style.position = 'relative';
                element.style.zIndex = '10';
                element.style.animation = 'none';
                element.style.transition = 'none';
                element.style.transform = 'none';
                
                // Remove classes that might hide it
                element.classList.remove('d-none', 'hidden', 'fade', 'collapse');
                element.classList.add('d-block', 'show');

                // Prevent any event listeners from hiding it
                const newElement = element.cloneNode(true);
                element.parentNode.replaceChild(newElement, element);
            }
        });
    }

    // Force display ngay lập tức
    forceDisplayInstructions();

    // Force display sau 1 giây (phòng trường hợp có script khác can thiệp)
    setTimeout(forceDisplayInstructions, 1000);

    // Force display mỗi 5 giây
    setInterval(forceDisplayInstructions, 5000);

    // Observe DOM changes và force display khi có thay đổi
    const observer = new MutationObserver(function(mutations) {
        let shouldForce = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' || mutation.type === 'childList') {
                shouldForce = true;
            }
        });
        if (shouldForce) {
            setTimeout(forceDisplayInstructions, 100);
        }
    });

    // Observe document body
    observer.observe(document.body, {
        attributes: true,
        childList: true,
        subtree: true,
        attributeFilter: ['style', 'class']
    });
});

// Override any Bootstrap/jQuery methods that might hide alerts
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        // Override Bootstrap alert close
        $(document).on('click', '.alert .btn-close', function(e) {
            const alert = $(this).closest('.alert-info');
            if (alert.length > 0 && alert.attr('id') === 'instructions-alert') {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Override fade methods for instructions
        const originalFadeOut = $.fn.fadeOut;
        $.fn.fadeOut = function() {
            if (this.hasClass('alert-info') || this.attr('id') === 'instructions-alert') {
                return this; // Do nothing
            }
            return originalFadeOut.apply(this, arguments);
        };

        const originalHide = $.fn.hide;
        $.fn.hide = function() {
            if (this.hasClass('alert-info') || this.attr('id') === 'instructions-alert') {
                return this; // Do nothing
            }
            return originalHide.apply(this, arguments);
        };
    });
}
</script>
@endpush