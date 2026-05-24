<script>
/**
 * Modal Focus Management Utility
 * Maneja el foco en modales accesibles siguiendo WAI-ARIA
 */
window.ModalFocusManager = (function() {
    let lastFocusedElement = null;
    let focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    
    function trapFocus(modalElement) {
        const focusable = modalElement.querySelectorAll(focusableElements);
        if (focusable.length === 0) return;
        
        const firstFocusable = focusable[0];
        const lastFocusable = focusable[focusable.length - 1];
        
        modalElement.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }
        });
    }
    
    function rememberFocus() {
        lastFocusedElement = document.activeElement;
    }
    
    function restoreFocus() {
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }
    
    function focusFirstInput(modalElement) {
        const firstInput = modalElement.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
    
    return {
        trapFocus,
        rememberFocus,
        restoreFocus,
        focusFirstInput
    };
})();

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>