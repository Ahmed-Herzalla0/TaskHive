/**
 * TaskHive - Main JavaScript
 * الوظائف الأساسية المشتركة في جميع صفحات الموقع
 */

// ======================================== 
// 📌 تهيئة جميع المكونات عند تحميل الصفحة
// ======================================== 
document.addEventListener('DOMContentLoaded', function() {
    initAlerts();           // تهيئة التنبيهات
    initTooltips();         // تهيئة التلميحات
    initDropdowns();        // تهيئة القوائم المنسدلة
    initUnifiedNavbar();    // تهيئة شريط التنقل
    initMobileMenu();       // تهيئة قائمة الموبايل
    initSmoothScroll();     // تهيئة التمرير السلس
    initFormValidation();   // تهيئة التحقق من النماذج
});

// ======================================== 
// 🔔 التنبيهات (Alerts)
// إخفاء التنبيهات تلقائياً بعد 5 ثواني
// ======================================== 
function initAlerts() {
    // اختيار جميع التنبيهات (ما عدا الدائمة)
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(function(alert) {
        // إخفاء التنبيه بعد 5 ثواني
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s ease';  // تأثير التلاشي
            alert.style.opacity = '0';                      // جعله شفاف
            setTimeout(function() {
                alert.remove();  // حذفه من الصفحة
            }, 500);
        }, 5000);
    });

    // وظيفة زر الإغلاق (X)
    const closeButtons = document.querySelectorAll('.alert .close, .alert [data-dismiss="alert"]');
    closeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const alert = this.closest('.alert');  // الحصول على التنبيه الأب
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

// ======================================== 
// 💡 التلميحات (Tooltips)
// تظهر نص توضيحي عند التحويم على عنصر
// ======================================== 
function initTooltips() {
    // التحقق من وجود jQuery و Bootstrap tooltip
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
}

// ======================================== 
// 📋 القوائم المنسدلة (Dropdowns)
// ======================================== 
function initDropdowns() {
    // اختيار أزرار القوائم المنسدلة المخصصة
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle:not([data-toggle="dropdown"])');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();  // منع السلوك الافتراضي
            const dropdown = this.closest('.dropdown');
            if (dropdown) {
                dropdown.classList.toggle('open');  // فتح/إغلاق القائمة
            }
        });
    });

    // إغلاق القوائم عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            // إغلاق جميع القوائم المفتوحة
            document.querySelectorAll('.dropdown.open').forEach(function(dropdown) {
                dropdown.classList.remove('open');
            });
        }
    });
}

// ======================================== 
// 🍔 شريط التنقل الموحد (Unified Navbar)
// التحكم بقائمة الهامبرغر للموبايل
// ======================================== 
function initUnifiedNavbar() {
    const navbars = document.querySelectorAll('[data-th-navbar]');
    if (!navbars.length) {
        return;  // إذا لم يوجد شريط تنقل، نخرج
    }

    // دالة فتح/إغلاق القائمة
    const toggleMenu = (navbar) => {
        const toggle = navbar.querySelector('[data-th-toggle]');  // زر الهامبرغر
        const menu = navbar.querySelector('[data-th-menu]');       // القائمة
        const isOpen = navbar.classList.toggle('is-open');         // تبديل الحالة

        if (!toggle || !menu) {
            return;
        }

        // تحديث خصائص الوصولية (Accessibility)
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        menu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.classList.toggle('th-menu-open', isOpen);  // منع التمرير عند فتح القائمة

        if (isOpen) {
            // التركيز على أول عنصر قابل للنقر في القائمة
            const firstFocusable = menu.querySelector('a, button, input, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) {
                firstFocusable.focus({ preventScroll: true });
            }
        } else {
            // إرجاع التركيز لزر الهامبرغر
            toggle.focus({ preventScroll: true });
        }
    };

    // تطبيق على كل شريط تنقل
    navbars.forEach(function(navbar) {
        const toggle = navbar.querySelector('[data-th-toggle]');
        const menu = navbar.querySelector('[data-th-menu]');
        const overlay = navbar.querySelector('[data-th-overlay]');  // خلفية شفافة

        if (!toggle || !menu) {
            return;
        }

        // دالة إغلاق القائمة
        const closeMenu = function(focusToggle = false) {
            if (!navbar.classList.contains('is-open')) {
                return;  // القائمة مغلقة أصلاً
            }
            navbar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('th-menu-open');

            if (focusToggle) {
                toggle.focus({ preventScroll: true });
            }
        };

        // حدث النقر على زر الهامبرغر
        toggle.addEventListener('click', () => toggleMenu(navbar));
        
        // دعم لوحة المفاتيح (Enter أو Space)
        toggle.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleMenu(navbar);
            }
        });

        // إغلاق القائمة عند النقر على الخلفية الشفافة
        if (overlay) {
            overlay.addEventListener('click', () => closeMenu(true));
        }

        // إغلاق القائمة عند النقر على أي رابط
        menu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {  // فقط على الشاشات الصغيرة
                    closeMenu(true);
                }
            });
        });

        // إغلاق القائمة عند تكبير الشاشة
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeMenu();
            }
        });

        // إغلاق القائمة بزر Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMenu(true);
            }
        });
    });
}

// ======================================== 
// 📱 قائمة Bootstrap للموبايل
// للتوافق مع Bootstrap 3
// ======================================== 
function initMobileMenu() {
    const menuToggle = document.querySelector('.navbar-toggle, .mobile-menu-toggle');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (menuToggle && navbarCollapse) {
        menuToggle.addEventListener('click', function() {
            navbarCollapse.classList.toggle('in');  // إظهار/إخفاء القائمة
            this.classList.toggle('collapsed');
        });
    }
}

// ======================================== 
// 🎯 التمرير السلس (Smooth Scroll)
// للروابط التي تبدأ بـ #
// ======================================== 
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]:not([data-toggle])').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;  // تجاهل الروابط الفارغة
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',  // تمرير سلس
                    block: 'start'       // الوصول لبداية العنصر
                });
            }
        });
    });
}

// ======================================== 
// ✅ التحقق من صحة النماذج (Form Validation)
// ======================================== 
function initFormValidation() {
    // إضافة class لإظهار رسائل الخطأ عند الإرسال
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {  // إذا النموذج غير صالح
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // التحقق الفوري عند مغادرة الحقل
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    inputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');    // حقل غير صالح
                this.classList.remove('is-valid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');      // حقل صالح
            }
        });
    });
}

// ======================================== 
// 🖼️ معاينة صورة البروفايل
// تظهر الصورة قبل رفعها
// ======================================== 
function previewProfileImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0] && preview) {
        const reader = new FileReader();  // قارئ الملفات
        reader.onload = function(e) {
            preview.src = e.target.result;  // عرض الصورة
            preview.classList.remove('hidden-preview');
            
            // إخفاء الأيقونة الافتراضية
            const placeholder = preview.closest('.profile-preview').querySelector('.glyphicon-user, .fa-user');
            if (placeholder) {
                placeholder.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);  // قراءة الملف كـ Data URL
    }
}

// ======================================== 
// 📎 معاينة ملفات التسليم
// ======================================== 
function previewSubmissionFile(input, previewContainerId) {
    const container = document.getElementById(previewContainerId);
    if (!container) return;
    
    container.innerHTML = '';  // مسح المحتوى السابق
    
    if (input.files && input.files.length > 0) {
        // عرض كل ملف
        Array.from(input.files).forEach(function(file) {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-preview-item';
            
            // اختيار الأيقونة حسب نوع الملف
            const icon = file.type.startsWith('image/') ? 'picture' : 'file';
            fileItem.innerHTML = '<span class="glyphicon glyphicon-' + icon + '"></span> ' + file.name;
            
            container.appendChild(fileItem);
        });
    }
}

// ======================================== 
// 🗑️ تأكيد الحذف
// تظهر رسالة تأكيد قبل الحذف
// ======================================== 
function confirmDelete(message) {
    return confirm(message || 'هل أنت متأكد من الحذف؟');
}

// ======================================== 
// 📋 نسخ النص للحافظة (Clipboard)
// ======================================== 
function copyToClipboard(text, successMessage) {
    if (navigator.clipboard) {
        // الطريقة الحديثة
        navigator.clipboard.writeText(text).then(function() {
            if (successMessage) {
                showToast(successMessage, 'success');
            }
        });
    } else {
        // طريقة بديلة للمتصفحات القديمة
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

// ======================================== 
// 🔔 إشعارات Toast
// رسائل صغيرة تظهر وتختفي تلقائياً
// ======================================== 
function showToast(message, type) {
    type = type || 'info';  // النوع الافتراضي: معلومات
    
    // إنشاء عنصر Toast
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    toast.innerHTML = message;
    
    // إضافة للصفحة
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // تأثير الظهور
    setTimeout(function() {
        toast.classList.add('show');
    }, 10);
    
    // الإخفاء بعد 3 ثواني
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() {
            toast.remove();
        }, 300);
    }, 3000);
}

// ======================================== 
// 📅 تنسيق التاريخ
// عرض التاريخ بالعربي
// ======================================== 
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('ar-SA', options);  // التنسيق العربي
}

// ======================================== 
// ⏱️ دالة Debounce
// تأخير التنفيذ لتجنب الاستدعاءات المتكررة
// مفيدة لمربعات البحث
// ======================================== 
function debounce(func, wait) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);  // إلغاء المؤقت السابق
        timeout = setTimeout(function() {
            func.apply(context, args);  // تنفيذ بعد الانتظار
        }, wait);
    };
}

// ======================================== 
// 👁️ إظهار/إخفاء كلمة المرور
// ======================================== 
function togglePasswordVisibility(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    if (input) {
        if (input.type === 'password') {
            input.type = 'text';  // إظهار كلمة المرور
            toggleBtn.innerHTML = '<span class="glyphicon glyphicon-eye-close"></span>';
        } else {
            input.type = 'password';  // إخفاء كلمة المرور
            toggleBtn.innerHTML = '<span class="glyphicon glyphicon-eye-open"></span>';
        }
    }
}

// ======================================== 
// ⏳ حالة التحميل للأزرار
// تعطيل الزر وإظهار "جاري..."
// ======================================== 
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;  // تعطيل الزر
        button.dataset.originalText = button.innerHTML;  // حفظ النص الأصلي
        button.innerHTML = '<span class="glyphicon glyphicon-refresh spinning"></span> جاري...';
    } else {
        button.disabled = false;  // تفعيل الزر
        button.innerHTML = button.dataset.originalText || button.innerHTML;  // إرجاع النص الأصلي
    }
}
