/**
 * TaskHive - Main JavaScript
 * Common functionality across all pages
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initAlerts();
    initTooltips();
    initDropdowns();
    initUnifiedNavbar();
    initMobileMenu();
    initSmoothScroll();
    initFormValidation();
});

/**
 * Auto-dismiss alerts after 5 seconds
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Close button functionality
    const closeButtons = document.querySelectorAll('.alert .close, .alert [data-dismiss="alert"]');
    closeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const alert = this.closest('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }
        });
    });
}

/**
 * Initialize Bootstrap tooltips
 */
function initTooltips() {
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
}

/**
 * Initialize dropdowns
 */
function initDropdowns() {
    // Custom dropdown handling if needed
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle:not([data-toggle="dropdown"])');
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.closest('.dropdown');
            if (dropdown) {
                dropdown.classList.toggle('open');
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown.open').forEach(function(dropdown) {
                dropdown.classList.remove('open');
            });
        }
    });
}

/**
 * Unified hamburger navbar
 */
function initUnifiedNavbar() {
    const navbars = document.querySelectorAll('[data-th-navbar]');
    if (!navbars.length) {
        return;
    }

    const toggleMenu = (navbar) => {
        const toggle = navbar.querySelector('[data-th-toggle]');
        const menu = navbar.querySelector('[data-th-menu]');
        const isOpen = navbar.classList.toggle('is-open');

        if (!toggle || !menu) {
            return;
        }

        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.classList.toggle('th-menu-open', isOpen);

        if (isOpen) {
            const firstFocusable = menu.querySelector('a, button, input, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) {
                firstFocusable.focus({ preventScroll: true });
            }
        } else {
            toggle.focus({ preventScroll: true });
        }
    };

    navbars.forEach(function(navbar) {
        const toggle = navbar.querySelector('[data-th-toggle]');
        const menu = navbar.querySelector('[data-th-menu]');
        const overlay = navbar.querySelector('[data-th-overlay]');

        if (!toggle || !menu) {
            return;
        }

        const closeMenu = function(focusToggle = false) {
            if (!navbar.classList.contains('is-open')) {
                return;
            }
            navbar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('th-menu-open');

            if (focusToggle) {
                toggle.focus({ preventScroll: true });
            }
        };

        toggle.addEventListener('click', () => toggleMenu(navbar));
        toggle.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleMenu(navbar);
            }
        });

        if (overlay) {
            overlay.addEventListener('click', () => closeMenu(true));
        }

        menu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    closeMenu(true);
                }
            });
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMenu(true);
            }
        });
    });
}

/**
 * Mobile menu toggle
 */
function initMobileMenu() {
    const menuToggle = document.querySelector('.navbar-toggle, .mobile-menu-toggle');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (menuToggle && navbarCollapse) {
        menuToggle.addEventListener('click', function() {
            navbarCollapse.classList.toggle('in');
            this.classList.toggle('collapsed');
        });
    }
}

/**
 * Smooth scroll for anchor links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]:not([data-toggle])').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Form validation helpers
 */
function initFormValidation() {
    // Add 'was-validated' class on submit for Bootstrap validation styles
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Real-time validation feedback
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    inputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });
}

/**
 * Profile image preview
 */
function previewProfileImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0] && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden-preview');
            
            // Hide placeholder icon if exists
            const placeholder = preview.closest('.profile-preview').querySelector('.glyphicon-user, .fa-user');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * File input preview for submissions
 */
function previewSubmissionFile(input, previewContainerId) {
    const container = document.getElementById(previewContainerId);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach(function(file) {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-preview-item';
            
            const icon = file.type.startsWith('image/') ? 'picture' : 'file';
            fileItem.innerHTML = '<span class="glyphicon glyphicon-' + icon + '"></span> ' + file.name;
            
            container.appendChild(fileItem);
        });
    }
}

/**
 * Confirm delete action
 */
function confirmDelete(message) {
    return confirm(message || 'هل أنت متأكد من الحذف؟');
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text, successMessage) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            if (successMessage) {
                showToast(successMessage, 'success');
            }
        });
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        if (successMessage) {
            showToast(successMessage, 'success');
        }
    }
}

/**
 * Show toast notification
 */
function showToast(message, type) {
    type = type || 'info';
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    toast.innerHTML = message;
    
    // Add to page
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Animate in
    setTimeout(function() {
        toast.classList.add('show');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, 3000);
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('ar-SA', options);
}

/**
 * Debounce function for search inputs
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

/**
 * Toggle password visibility
 */
function togglePasswordVisibility(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    if (input) {
        if (input.type === 'password') {
            input.type = 'text';
            toggleBtn.innerHTML = '<span class="glyphicon glyphicon-eye-close"></span>';
        } else {
            input.type = 'password';
            toggleBtn.innerHTML = '<span class="glyphicon glyphicon-eye-open"></span>';
        }
    }
}

/**
 * Loading state for buttons
 */
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="glyphicon glyphicon-refresh spinning"></span> جاري...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }
}
