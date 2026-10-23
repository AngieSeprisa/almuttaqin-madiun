document.addEventListener('DOMContentLoaded', function () {
  const sidebarToggleBtns = document.querySelectorAll('.js-sidebar-toggle');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');
  const brandWrapper = document.querySelector('.brand-wrapper');
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const body = document.body;

  // Restore Theme state
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark') {
    document.documentElement.classList.add('dark-mode');
    body.classList.add('dark-mode');
  }

  // Theme Toggle Button Handler (Only on Dashboard)
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', function () {
      const isDark = document.documentElement.classList.toggle('dark-mode');
      body.classList.toggle('dark-mode', isDark);
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
  }

  // Restore sidebar state from localStorage for desktop
  const isDesktop = window.innerWidth > 768;
  const savedSidebar = localStorage.getItem('sidebar_collapsed');

  if (isDesktop && savedSidebar === 'true') {
    body.classList.add('sidebar-collapsed');
  }

  // Toggle button click handler
  sidebarToggleBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (window.innerWidth <= 768) {
        body.classList.toggle('sidebar-mobile-open');
      } else {
        body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', body.classList.contains('sidebar-collapsed'));
      }
    });
  });

  // Clicking brand logo in collapsed mode expands sidebar
  if (brandWrapper) {
    brandWrapper.addEventListener('click', function (e) {
      if (window.innerWidth > 768 && body.classList.contains('sidebar-collapsed')) {
        e.preventDefault();
        body.classList.remove('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', 'false');
      }
    });
  }

  // Close sidebar when clicking outside on mobile
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function () {
      body.classList.remove('sidebar-mobile-open');
    });
  }

  // Submenu Dropdown Toggles (Perizinan, Rekap Absensi, etc.)
  document.querySelectorAll('.nav-group').forEach(group => {
    const toggleBtn = group.querySelector('.nav-item-has-sub');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (body.classList.contains('sidebar-collapsed')) {
          body.classList.remove('sidebar-collapsed');
          localStorage.setItem('sidebar_collapsed', 'false');
          group.classList.add('open');
        } else {
          group.classList.toggle('open');
        }

        if (group.classList.contains('open')) {
          setTimeout(() => {
            group.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }, 80);
        }
      });
    }
  });


  // Close sidebar on mobile when navigating (both nav-item and subnav-item)
  document.querySelectorAll('.sidebar-nav .nav-item:not(.nav-item-has-sub), .sidebar-nav .subnav-item').forEach(item => {
    item.addEventListener('click', function () {
      if (window.innerWidth <= 768) {
        body.classList.remove('sidebar-mobile-open');
      }
    });
  });

  // Close sidebar on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && body.classList.contains('sidebar-mobile-open')) {
      body.classList.remove('sidebar-mobile-open');
    }
  });

  // Handle window resize
  window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
      body.classList.remove('sidebar-mobile-open');
      const saved = localStorage.getItem('sidebar_collapsed');
      if (saved === 'true') {
        body.classList.add('sidebar-collapsed');
      } else {
        body.classList.remove('sidebar-collapsed');
      }
    }
  });

  // ==========================================
  // UNIVERSAL APP MODAL CONTROLLER
  // ==========================================
  function openAppModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('is-active');
      document.body.style.overflow = 'hidden';
      var firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
      if (firstInput) {
        setTimeout(function() { firstInput.focus(); }, 100);
      }
    }
  }

  function closeAppModal(modal) {
    if (modal) {
      modal.classList.remove('is-active');
      if (!document.querySelector('.app-modal.is-active')) {
        document.body.style.overflow = '';
      }
    }
  }

  document.addEventListener('click', function (e) {
    var targetBtn = e.target.closest('[data-modal-target]');
    if (targetBtn) {
      e.preventDefault();
      var targetId = targetBtn.getAttribute('data-modal-target');
      openAppModal(targetId);
      return;
    }

    var closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      e.preventDefault();
      closeAppModal(closeBtn.closest('.app-modal'));
      return;
    }

    if (e.target.classList.contains('app-modal')) {
      closeAppModal(e.target);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var activeModal = document.querySelector('.app-modal.is-active');
      if (activeModal) {
        closeAppModal(activeModal);
      }
    }
  });

  // ==========================================
  // SWEETALERT2 UNIVERSAL CONFIRMATION HANDLER
  // ==========================================
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-swal-confirm');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    var form = btn.closest('form');
    var title = btn.getAttribute('data-title') || 'Konfirmasi';
    var text = btn.getAttribute('data-text') || '';
    var html = btn.getAttribute('data-html') || '';
    var icon = btn.getAttribute('data-icon') || 'warning';
    var confirmText = btn.getAttribute('data-confirm-text') || 'Ya, Lanjutkan';
    var cancelText = btn.getAttribute('data-cancel-text') || 'Batal';
    var confirmColor = btn.getAttribute('data-confirm-color') || '#DC2626';

    if (typeof Swal === 'undefined') {
      var plainMsg = title + (text ? '\n\n' + text : '');
      if (confirm(plainMsg)) {
        if (form) {
          if (btn.name && btn.value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = btn.name;
            input.value = btn.value;
            form.appendChild(input);
          }
          form.submit();
        }
      }
      return;
    }

    var isDark = document.documentElement.classList.contains('dark-mode') || document.body.classList.contains('dark-mode');

    var swalOptions = {
      title: title,
      icon: icon,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: cancelText,
      confirmButtonColor: confirmColor,
      cancelButtonColor: isDark ? '#334155' : '#94A3B8',
      reverseButtons: true,
      focusCancel: true,
      background: isDark ? '#141C28' : '#FFFFFF',
      color: isDark ? '#F8FAFC' : '#111827',
      backdrop: 'rgba(15, 23, 42, 0.45)',
      customClass: {
        popup: 'swal2-modern-popup',
        title: 'swal2-modern-title',
        htmlContainer: 'swal2-modern-html',
        confirmButton: 'swal2-modern-btn swal2-btn-confirm',
        cancelButton: 'swal2-modern-btn swal2-btn-cancel'
      }
    };

    if (html) {
      swalOptions.html = html;
    } else if (text) {
      swalOptions.text = text;
    }

    Swal.fire(swalOptions).then(function (result) {
      if (result.isConfirmed && form) {
        if (btn.name && btn.value) {
          var hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = btn.name;
          hidden.value = btn.value;
          form.appendChild(hidden);
        }
        form.submit();
      }
    });
  });
});
