import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ===== Loading Overlay =====
window.showLoading = function(text = 'Processando...') {
    const overlay = document.getElementById('loading-overlay');
    if (!overlay) return;

    const loadingText = overlay.querySelector('.loading-text');
    if (loadingText) loadingText.textContent = text;

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
};

window.hideLoading = function() {
    const overlay = document.getElementById('loading-overlay');
    if (!overlay) return;

    overlay.classList.remove('active');
    document.body.style.overflow = '';
};

// ===== DOM Ready =====
document.addEventListener('DOMContentLoaded', function() {
    // Page enter animation
    const mainContent = document.querySelector('main');
    if (mainContent) {
        mainContent.classList.add('page-enter');
    }

    // Auto-attach loading to forms with data-loading attribute
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const text = this.getAttribute('data-loading') || 'Processando...';
            showLoading(text);
        });
    });

    // Links with data-loading attribute
    document.querySelectorAll('a[data-loading]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey) return;
            const text = this.getAttribute('data-loading') || 'Carregando...';
            showLoading(text);
        });
    });

    // All POST/PUT/DELETE forms (except those with data-no-loading)
    document.querySelectorAll('form:not([data-no-loading])').forEach(form => {
        if (form.method.toLowerCase() === 'get' && !form.hasAttribute('data-loading')) return;
        if (form.hasAttribute('data-loading')) return;

        form.addEventListener('submit', function(e) {
            if (this.getAttribute('onsubmit')?.includes('confirm')) return;
            if (!this.checkValidity()) return;
            showLoading('Salvando...');
        });
    });

    // Navigation links in header/nav that trigger page loads
    document.querySelectorAll('a[href]:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not([data-no-loading])').forEach(link => {
        if (!link.closest('nav, header, .nav-link, [role="navigation"]')) return;
        if (link.hasAttribute('data-loading')) return;

        link.addEventListener('click', function(e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey) return;
            if (this.href === window.location.href) return;

            setTimeout(() => {
                if (!document.hidden) {
                    showLoading('Carregando...');
                }
            }, 200);
        });
    });
});

// Hide loading on page show (back/forward navigation)
window.addEventListener('pageshow', function(event) {
    hideLoading();
});

// Safety timeout - hide loading after 15s to prevent infinite loading
let loadingTimeout;
const originalShowLoading = window.showLoading;
window.showLoading = function(text = 'Processando...') {
    originalShowLoading(text);
    clearTimeout(loadingTimeout);
    loadingTimeout = setTimeout(() => {
        hideLoading();
    }, 15000);
};

const originalHideLoading = window.hideLoading;
window.hideLoading = function() {
    clearTimeout(loadingTimeout);
    originalHideLoading();
};

Alpine.start();
