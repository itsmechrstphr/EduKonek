// ==========================================
// DASHBOARD JAVASCRIPT FUNCTIONALITY
// Enhanced with proper notification system and CRUD handlers
// ==========================================

/**
 * Initialize all dashboard functionality when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeDropdowns();
    initializeModals();
    initializeScrollAnimations();
    initializeEnhancedToastSystem();
    initializeFormValidation();
    
    // Display server-side messages as toasts
    displayServerMessages();
});

/**
 * Display server-side success/error messages as toasts
 */
function displayServerMessages() {
    // Check for success message
    const successDiv = document.querySelector('.alert-success');
    if (successDiv) {
        const message = successDiv.textContent.trim();
        showEnhancedToast(message, 'success', 5000);
    }
    
    // Check for error message
    const errorDiv = document.querySelector('.alert-danger');
    if (errorDiv) {
        const message = errorDiv.textContent.trim();
        showEnhancedToast(message, 'error', 5000);
    }
}

/**
 * Initialize dropdown menu functionality
 */
function initializeDropdowns() {
    const dropdownButtons = document.querySelectorAll('.dropdown-toggle');
    
    dropdownButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.dropdown');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m !== menu) m.classList.remove('show');
            });
            
            // Toggle current dropdown
            menu.classList.toggle('show');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
}

/**
 * Initialize modal functionality
 */
function initializeModals() {
    // Bootstrap modal initialization is automatic
    // Additional custom modal handling can be added here
}

/**
 * Initialize scroll animations using IntersectionObserver
 */
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, observerOptions);

    // Observe dashboard sections
    const sections = document.querySelectorAll('.dashboard-section');
    sections.forEach(section => {
        section.classList.add('fade-in');
        observer.observe(section);
    });

    // Observe cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('scale-in');
        observer.observe(card);
    });
}

/**
 * Initialize enhanced toast notification system
 */
function initializeEnhancedToastSystem() {
    // Create toast container if it doesn't exist
    if (!document.querySelector('.enhanced-toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.className = 'enhanced-toast-container';
        document.body.appendChild(toastContainer);
    }

    // Add styles if not already present
    if (!document.querySelector('#toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .enhanced-toast-container {
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            }
            .enhanced-toast {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                margin-bottom: 10px;
                padding: 16px;
                display: flex;
                align-items: center;
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s ease;
                border-left: 4px solid;
            }
            .enhanced-toast.show {
                transform: translateX(0);
                opacity: 1;
            }
            .enhanced-toast.success { border-left-color: #28a745; }
            .enhanced-toast.error { border-left-color: #dc3545; }
            .enhanced-toast.warning { border-left-color: #ffc107; }
            .enhanced-toast.info { border-left-color: #17a2b8; }
            .enhanced-toast-icon {
                margin-right: 12px;
                font-size: 20px;
            }
            .enhanced-toast-content {
                flex: 1;
                color: #333;
            }
            .enhanced-toast-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #6c757d;
                padding: 0;
                margin-left: 12px;
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Show enhanced toast notification
 * @param {string} message - The message to display
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in milliseconds
 */
function showEnhancedToast(message, type = 'info', duration = 5000) {
    const toastContainer = document.querySelector('.enhanced-toast-container');
    if (!toastContainer) return;

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    const toast = document.createElement('div');
    toast.className = `enhanced-toast ${type}`;
    toast.innerHTML = `
        <span class="enhanced-toast-icon">${icons[type] || icons.info}</span>
        <div class="enhanced-toast-content">${message}</div>
        <button class="enhanced-toast-close" onclick="hideEnhancedToast(this)">&times;</button>
    `;

    toastContainer.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 100);

    // Auto-hide after duration
    if (duration > 0) {
        setTimeout(() => {
            hideEnhancedToast(toast.querySelector('.enhanced-toast-close'));
        }, duration);
    }
}

/**
 * Hide enhanced toast notification
 * @param {HTMLElement} closeBtn - The close button element
 */
function hideEnhancedToast(closeBtn) {
    const toast = closeBtn.closest('.enhanced-toast');
    if (toast) {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });

            // Password validation
            const passwordFields = form.querySelectorAll('input[type="password"][required]');
            passwordFields.forEach(field => {
                if (field.value && field.value.length < 6) {
                    field.classList.add('is-invalid');
                    isValid = false;
                    showEnhancedToast('Password must be at least 6 characters', 'warning', 3000);
                }
            });

            if (!isValid) {
                e.preventDefault();
                showEnhancedToast('Please fill in all required fields correctly', 'warning', 3000);
            }
        });

        // Remove invalid class on input
        form.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
    });
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if valid
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Create a modal element dynamically
 * @param {string} id - Modal ID
 * @param {string} title - Modal title
 * @param {string} content - Modal content HTML
 * @returns {HTMLElement} The modal element
 */
function createModal(id, title, content) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = id;
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        </div>
    `;
    return modal;
}

/**
 * Scroll to a section smoothly
 * @param {string} selector - The section selector
 */
function scrollToSection(selector) {
    const element = document.querySelector(selector);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// Export functions for use in other scripts
window.showEnhancedToast = showEnhancedToast;
window.hideEnhancedToast = hideEnhancedToast;
window.createModal = createModal;
window.scrollToSection = scrollToSection;
