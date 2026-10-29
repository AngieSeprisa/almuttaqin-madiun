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

  function updateBrandTooltip() {
    if (brandWrapper) {
      if (window.innerWidth > 768 && body.classList.contains('sidebar-collapsed')) {
        brandWrapper.setAttribute('title', 'Buka Menu Sidebar');
      } else {
        const origTitle = brandWrapper.getAttribute('data-original-title') || 'Beranda';
        brandWrapper.setAttribute('title', origTitle);
      }
    }
  }

  // Restore sidebar state from localStorage for desktop
  const isDesktop = window.innerWidth > 768;
  const savedSidebar = localStorage.getItem('sidebar_collapsed');

  if (isDesktop && savedSidebar === 'true') {
    body.classList.add('sidebar-collapsed');
  }
  updateBrandTooltip();

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
        updateBrandTooltip();
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
        updateBrandTooltip();
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
  // GAMBAR 5 CENTERED CONFIRMATION MODAL (.fi-confirm-*)
  // ==========================================
  function ensureGlobalFiConfirmModal() {
    var existing = document.getElementById('globalFiConfirmModal');
    if (existing) return existing;

    var backdrop = document.createElement('div');
    backdrop.id = 'globalFiConfirmModal';
    backdrop.className = 'fi-confirm-backdrop';
    backdrop.setAttribute('role', 'dialog');
    backdrop.setAttribute('aria-modal', 'true');
    backdrop.innerHTML =
      '<div class="fi-confirm-dialog">' +
        '<button type="button" class="fi-confirm-close" data-fi-confirm-close aria-label="Tutup">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<line x1="18" y1="6" x2="6" y2="18"></line>' +
            '<line x1="6" y1="6" x2="18" y2="18"></line>' +
          '</svg>' +
        '</button>' +
        '<div class="fi-confirm-icon is-danger" data-fi-confirm-icon>' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<polyline points="3 6 5 6 21 6"></polyline>' +
            '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>' +
            '<line x1="10" y1="11" x2="10" y2="17"></line>' +
            '<line x1="14" y1="11" x2="14" y2="17"></line>' +
          '</svg>' +
        '</div>' +
        '<h3 class="fi-confirm-title" data-fi-confirm-title>Konfirmasi</h3>' +
        '<p class="fi-confirm-desc" data-fi-confirm-desc>Apakah Anda yakin ingin melanjutkan tindakan ini?</p>' +
        '<div class="fi-confirm-actions">' +
          '<button type="button" class="fi-confirm-btn-cancel" data-fi-confirm-cancel>Batal</button>' +
          '<button type="button" class="fi-confirm-btn-submit" data-fi-confirm-submit>Hapus</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(backdrop);

    backdrop.addEventListener('click', function (ev) {
      if (ev.target === backdrop || ev.target.closest('[data-fi-confirm-close]') || ev.target.closest('[data-fi-confirm-cancel]')) {
        backdrop.classList.remove('is-open');
      }
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && backdrop.classList.contains('is-open')) {
        backdrop.classList.remove('is-open');
      }
    });

    return backdrop;
  }

  window.showFiConfirmModal = function (opts) {
    opts = opts || {};
    var modal = ensureGlobalFiConfirmModal();
    var iconWrap = modal.querySelector('[data-fi-confirm-icon]');
    var titleEl = modal.querySelector('[data-fi-confirm-title]');
    var descEl = modal.querySelector('[data-fi-confirm-desc]');
    var cancelBtn = modal.querySelector('[data-fi-confirm-cancel]');
    var submitBtn = modal.querySelector('[data-fi-confirm-submit]');

    var variant = opts.variant || 'danger';
    iconWrap.className = 'fi-confirm-icon ' + (variant === 'primary' ? 'is-primary' : 'is-danger');
    if (variant === 'primary') {
      iconWrap.innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<polyline points="20 6 9 17 4 12"></polyline>' +
        '</svg>';
    } else {
      iconWrap.innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<polyline points="3 6 5 6 21 6"></polyline>' +
          '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>' +
          '<line x1="10" y1="11" x2="10" y2="17"></line>' +
          '<line x1="14" y1="11" x2="14" y2="17"></line>' +
        '</svg>';
    }

    titleEl.textContent = opts.title || 'Konfirmasi';
    descEl.textContent = opts.description || 'Apakah Anda yakin ingin melakukan tindakan ini?';
    cancelBtn.textContent = opts.cancelText || 'Batal';
    submitBtn.textContent = opts.confirmText || (variant === 'primary' ? 'Lanjutkan' : 'Hapus');
    submitBtn.disabled = false;
    submitBtn.className = 'fi-confirm-btn-submit' + (variant === 'primary' ? ' is-primary' : '');

    var newSubmit = submitBtn.cloneNode(true);
    submitBtn.parentNode.replaceChild(newSubmit, submitBtn);

    newSubmit.addEventListener('click', function () {
      if (typeof opts.onConfirm === 'function') {
        var res = opts.onConfirm(newSubmit, modal);
        if (res && typeof res.then === 'function') {
          newSubmit.disabled = true;
          newSubmit.textContent = 'Memproses...';
          res.finally(function () {
            modal.classList.remove('is-open');
          });
          return;
        }
      }
      modal.classList.remove('is-open');
    });

    modal.classList.add('is-open');
  };

  // ==========================================
  // UNIVERSAL CONFIRMATION HANDLER (GAMBAR 5 + SWAL FALLBACK)
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
    var confirmText = btn.getAttribute('data-confirm-text') || 'Hapus';
    var cancelText = btn.getAttribute('data-cancel-text') || 'Batal';
    var confirmColor = (btn.getAttribute('data-confirm-color') || '#DC2626').toUpperCase();

    function submitTargetForm() {
      if (!form) return;
      if (btn.name && btn.value) {
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = btn.name;
        hidden.value = btn.value;
        form.appendChild(hidden);
      }
      form.submit();
    }

    if (btn.closest('.fi-ta-ctn') || typeof Swal === 'undefined') {
      var cleanDesc = text;
      if (!cleanDesc && html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html.replace(/<br\s*\/?>/gi, ' ');
        cleanDesc = (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
      }
      var isPositive = confirmColor.indexOf('10B981') !== -1 || confirmColor.indexOf('059669') !== -1 || confirmColor.indexOf('0052FF') !== -1 || confirmColor.indexOf('2563EB') !== -1;
      window.showFiConfirmModal({
        title: title.replace(/\?$/, ''),
        description: cleanDesc || 'Apakah Anda yakin ingin melakukan tindakan ini?',
        confirmText: confirmText,
        cancelText: cancelText,
        variant: isPositive ? 'primary' : 'danger',
        onConfirm: submitTargetForm
      });
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
      if (result.isConfirmed) {
        submitTargetForm();
      }
    });
  });

  // ==========================================
  // UNIVERSAL FILAMENT V4 TABLE ENHANCER (.fi-ta-ctn)
  // ==========================================
  function humanizeFilterLabel(ctrl) {
    var explicitLabel = ctrl.getAttribute('aria-label') || ctrl.getAttribute('title');
    if (explicitLabel && explicitLabel.length < 40) return explicitLabel;

    if (ctrl.tagName === 'SELECT' && ctrl.options.length > 0) {
      var firstOpt = (ctrl.options[0].textContent || '').trim();
      if (/^semua\s+/i.test(firstOpt)) {
        return firstOpt.replace(/^semua\s+/i, '').trim();
      }
    }

    var rawName = (ctrl.getAttribute('name') || ctrl.id || 'Filter')
      .replace(/^filter_/, '')
      .replace(/_id$/, '')
      .replace(/_/g, ' ');
    return rawName.charAt(0).toUpperCase() + rawName.slice(1);
  }

  function downloadSelectedCsv(table, selectedRows) {
    var headers = [];
    var ths = Array.from(table.tHead.rows[0].cells);
    var skipCols = new Set();

    ths.forEach(function (th, idx) {
      var txt = (th.textContent || '').replace(/\s+/g, ' ').trim();
      if (th.classList.contains('fi-ta-th-check') || /^aksi/i.test(txt) || (!txt && idx === ths.length - 1)) {
        skipCols.add(idx);
      } else {
        headers.push('"' + txt.replace(/"/g, '""') + '"');
      }
    });

    var lines = [headers.join(',')];
    selectedRows.forEach(function (tr) {
      var vals = [];
      Array.from(tr.cells).forEach(function (td, idx) {
        if (skipCols.has(idx)) return;
        var cellTxt = (td.textContent || '').replace(/\s+/g, ' ').trim();
        vals.push('"' + cellTxt.replace(/"/g, '""') + '"');
      });
      lines.push(vals.join(','));
    });

    var csvContent = '\uFEFF' + lines.join('\r\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'data-terpilih-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
  }

  function initFilamentTables() {
    var candidateTables = document.querySelectorAll('.table-card table, table.permission-table, table.mapel-data-table');

    candidateTables.forEach(function (table) {
      if (table.closest('.fi-ta-ctn')) return;
      if (table.closest('form')) return;
      if (table.closest('.app-modal, .pk-modal-backdrop, [role="dialog"]')) return;
      if (table.id === 'tableSiswaAbsensi') return;
      if (!table.tHead || !table.tHead.rows.length || !table.tBodies.length) return;
      if (table.tHead.rows[0].cells.length < 3) return;

      var card = table.closest('.table-card, .mapel-table-card, .form-section-card');
      if (!card || card.classList.contains('fi-ta-enhanced')) return;

      card.classList.add('table-card', 'fi-ta-ctn', 'fi-ta-enhanced');
      card.style.overflow = 'visible';
      card.style.padding = '0';

      var tableWrap = table.parentElement;
      if (tableWrap && tableWrap !== card) {
        tableWrap.classList.add('table-wrapper', 'fi-ta-table-wrap');
      }
      table.classList.add('fi-ta-table');

      // Normalize header inline uppercase styles so Filament v4 sentence-case applies cleanly
      var headRow = table.tHead.rows[0];
      headRow.style.textTransform = 'none';
      headRow.style.letterSpacing = '0';
      Array.from(headRow.cells).forEach(function (th) {
        th.style.textTransform = 'none';
        th.style.letterSpacing = '0';
      });

      // Dock sibling mobile card list inside unified .fi-ta-ctn card
      var mobileList = card.querySelector('.mobile-card-list, .mobile-siswa-list');
      if (!mobileList && card.nextElementSibling && card.nextElementSibling.matches('.mobile-card-list, .mobile-siswa-list')) {
        mobileList = card.nextElementSibling;
        if (tableWrap && tableWrap.parentElement === card) {
          tableWrap.insertAdjacentElement('afterend', mobileList);
        } else {
          card.appendChild(mobileList);
        }
      }

      // Remove duplicate mobile pagination inside mobileList if desktop pagination already exists
      var desktopServerPagination = null;
      Array.from(card.children).forEach(function (child) {
        if (child !== mobileList && child !== tableWrap && (child.querySelector('nav[role="navigation"], .pagination') || child.matches('nav[role="navigation"]'))) {
          desktopServerPagination = child;
        }
      });
      if (mobileList && desktopServerPagination) {
        Array.from(mobileList.children).forEach(function (mc) {
          if (!mc.classList.contains('mobile-card-item') && !mc.classList.contains('mobile-siswa-card') && !mc.classList.contains('mobile-empty-card')) {
            if (mc.querySelector('nav, .pagination')) {
              mc.style.display = 'none';
            }
          }
        });
      }

      // Discover associated filter container (inside card or preceding sibling)
      var filterContainer = card.querySelector('.mapel-toolbar-wrap');
      if (!filterContainer) {
        var prev = card.previousElementSibling;
        if (prev && (prev.classList.contains('filter-card') || prev.classList.contains('mstr-topbar') || prev.querySelector('input[name="search"], select[onchange*="submit"]'))) {
          filterContainer = prev;
        }
      }

      var origForm = filterContainer ? filterContainer.querySelector('form') : null;
      var origSearchInput = filterContainer ? filterContainer.querySelector('input[name="search"], .mstr-search-input, input[type="text"]') : null;
      var origResetLink = filterContainer ? filterContainer.querySelector('a.filter-btn-reset, a.mapel-btn-reset, a[title*="Reset"]') : null;
      var quickTabsWrap = filterContainer ? filterContainer.querySelector('.mstr-tabs-wrap') : null;

      // Identify real data rows vs empty-state row
      var tbody = table.tBodies[0];
      var allBodyRows = Array.from(tbody.rows);
      var dataRows = [];
      var emptyRows = [];

      allBodyRows.forEach(function (tr) {
        var onlyCell = tr.cells.length === 1 ? tr.cells[0] : null;
        if (onlyCell && (onlyCell.colSpan > 1 || onlyCell.classList.contains('cell-empty'))) {
          onlyCell.colSpan = onlyCell.colSpan + 1;
          emptyRows.push(tr);
        } else {
          dataRows.push(tr);
        }
      });

      var mobileCards = mobileList
        ? Array.from(mobileList.querySelectorAll('.mobile-card-item, .mobile-siswa-card'))
        : [];

      // Inject Master Header Checkbox + Row Checkboxes + Synced Mobile Checkboxes
      var thCheck = document.createElement('th');
      thCheck.className = 'fi-ta-th-check';
      var masterCheck = document.createElement('input');
      masterCheck.type = 'checkbox';
      masterCheck.className = 'fi-ta-checkbox';
      masterCheck.setAttribute('aria-label', 'Pilih semua baris');
      thCheck.appendChild(masterCheck);
      headRow.insertBefore(thCheck, headRow.firstChild);

      var rowMeta = dataRows.map(function (tr, idx) {
        var tdCheck = document.createElement('td');
        tdCheck.className = 'fi-ta-td-check';
        var cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'fi-ta-checkbox';
        cb.setAttribute('aria-label', 'Pilih baris ' + (idx + 1));
        tdCheck.appendChild(cb);
        tr.insertBefore(tdCheck, tr.firstChild);

        var mCard = mobileCards[idx] || null;
        var mCb = null;
        if (mCard) {
          mCb = document.createElement('input');
          mCb.type = 'checkbox';
          mCb.className = 'fi-ta-checkbox';
          mCb.setAttribute('aria-label', 'Pilih item ' + (idx + 1));
          var titleWrap = mCard.querySelector('.mobile-siswa-nama-wrap, .mobile-card-title-wrap');
          if (titleWrap) {
            titleWrap.insertBefore(mCb, titleWrap.firstChild);
          } else {
            var wrapDiv = document.createElement('div');
            wrapDiv.className = 'fi-mobile-check-wrap';
            wrapDiv.appendChild(mCb);
            mCard.insertBefore(wrapDiv, mCard.firstChild);
          }
        }

        // Detect delete form inside row (or paired mobile card)
        var deleteForm = null;
        var formsInRow = tr.querySelectorAll('form');
        formsInRow.forEach(function (f) {
          var methodInput = f.querySelector('input[name="_method"]');
          var delBtn = f.querySelector('.action-btn-delete, .btn-delete, [title*="Hapus"], [aria-label*="Hapus"]');
          if ((methodInput && methodInput.value.toUpperCase() === 'DELETE') || delBtn) {
            deleteForm = f;
          }
        });

        return {
          idx: idx,
          tr: tr,
          cb: cb,
          mCard: mCard,
          mCb: mCb,
          selected: false,
          deleteForm: deleteForm,
          searchText: ((tr.textContent || '') + ' ' + (mCard ? mCard.textContent : '')).replace(/\s+/g, ' ').toLowerCase()
        };
      });

      var hasDeletableRows = rowMeta.some(function (m) { return !!m.deleteForm; });

      // Build Filament v4 Top Header Toolbar ([ ⋮ Aksi ] on Left | [ Search ] + [ Funnel Filter ] on Right)
      var toolbar = document.createElement('div');
      toolbar.className = 'fi-ta-header-toolbar';

      var toolbarLeft = document.createElement('div');
      toolbarLeft.className = 'fi-ta-toolbar-left';

      var bulkWrap = document.createElement('div');
      bulkWrap.className = 'fi-ta-bulk-wrap';
      var bulkBtn = document.createElement('button');
      bulkBtn.type = 'button';
      bulkBtn.className = 'fi-ta-bulk-btn is-disabled';
      bulkBtn.disabled = true;
      bulkBtn.innerHTML =
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
          '<path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />' +
        '</svg>' +
        '<span>Aksi</span>' +
        '<span class="fi-ta-bulk-count" style="display:none;">0</span>';

      var bulkMenu = document.createElement('div');
      bulkMenu.className = 'fi-ta-bulk-menu';

      bulkWrap.appendChild(bulkBtn);
      bulkWrap.appendChild(bulkMenu);
      toolbarLeft.appendChild(bulkWrap);

      // Dock quick tab pills (e.g. Master Kelas / Master Jam Pelajaran) right beside Bulk Actions
      if (quickTabsWrap) {
        toolbarLeft.appendChild(quickTabsWrap);
      } else if (filterContainer && !origForm) {
        var quickLinks = filterContainer.querySelectorAll('a.btn');
        if (quickLinks.length > 0) {
          var pillGroup = document.createElement('div');
          pillGroup.style.cssText = 'display:flex; flex-wrap:wrap; gap:6px; align-items:center;';
          quickLinks.forEach(function (lnk) { pillGroup.appendChild(lnk); });
          toolbarLeft.appendChild(pillGroup);
        }
      }

      // Right Toolbar: Search + Funnel Filter Popover (Gambar 3 & Gambar 4)
      var toolbarRight = document.createElement('div');
      toolbarRight.className = 'fi-ta-toolbar-right';

      var searchWrap = document.createElement('div');
      searchWrap.className = 'fi-ta-search-wrap';
      var searchPlaceholder = origSearchInput ? (origSearchInput.getAttribute('placeholder') || 'Cari data...') : 'Cari data...';
      var initialSearchVal = origSearchInput ? (origSearchInput.value || '') : '';
      searchWrap.innerHTML =
        '<svg class="fi-ta-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
          '<circle cx="11" cy="11" r="8"></circle>' +
          '<line x1="21" y1="21" x2="16.65" y2="16.65"></line>' +
        '</svg>' +
        '<input type="text" class="fi-ta-search-input" placeholder="' + searchPlaceholder.replace(/"/g, '&quot;') + '">' +
        '<button type="button" class="fi-ta-search-clear" title="Bersihkan pencarian" aria-label="Bersihkan pencarian">' +
          '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
            '<line x1="18" y1="6" x2="6" y2="18"></line>' +
            '<line x1="6" y1="6" x2="18" y2="18"></line>' +
          '</svg>' +
        '</button>';

      var searchInput = searchWrap.querySelector('.fi-ta-search-input');
      var searchClear = searchWrap.querySelector('.fi-ta-search-clear');
      searchInput.value = initialSearchVal;
      if (initialSearchVal) searchClear.classList.add('is-visible');

      // Funnel Filter Popover right next to Search
      var filterWrap = document.createElement('div');
      filterWrap.className = 'fi-ta-filter-wrap';
      filterWrap.innerHTML =
        '<button type="button" class="fi-ta-filter-btn" title="Filter Data" aria-label="Filter Data">' +
          '<svg viewBox="0 0 20 20" fill="currentColor">' +
            '<path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />' +
          '</svg>' +
          '<span class="fi-ta-filter-badge">0</span>' +
        '</button>' +
        '<div class="fi-ta-filter-popover">' +
          '<div class="fi-ta-filter-head">' +
            '<span class="fi-ta-filter-title">Filter</span>' +
            '<button type="button" class="fi-ta-filter-reset">Reset</button>' +
          '</div>' +
          '<div class="fi-ta-filter-body"></div>' +
          '<div class="fi-ta-filter-actions">' +
            '<button type="button" class="fi-ta-filter-apply" style="width:100%;">Terapkan filter</button>' +
          '</div>' +
        '</div>';

      var filterBtn = filterWrap.querySelector('.fi-ta-filter-btn');
      var filterBadge = filterWrap.querySelector('.fi-ta-filter-badge');
      var filterPopover = filterWrap.querySelector('.fi-ta-filter-popover');
      var filterBody = filterWrap.querySelector('.fi-ta-filter-body');
      var filterResetBtn = filterWrap.querySelector('.fi-ta-filter-reset');
      var filterApplyBtn = filterWrap.querySelector('.fi-ta-filter-apply');

      // Populate Filter Popover from server <form> controls OR auto-detected client column filters
      var syncedServerControls = [];
      var clientColumnFilters = [];

      if (origForm) {
        var formControls = Array.from(origForm.querySelectorAll('select, input[type="date"], input[type="month"]'));
        formControls.forEach(function (ctrl) {
          if (!ctrl.name || ctrl.name === 'search') return;
          var fieldWrap = document.createElement('div');
          fieldWrap.className = 'fi-ta-filter-field';
          var lbl = document.createElement('label');
          lbl.className = 'fi-ta-filter-label';
          lbl.textContent = humanizeFilterLabel(ctrl);

          var clone = ctrl.cloneNode(true);
          clone.removeAttribute('id');
          clone.removeAttribute('onchange');
          clone.removeAttribute('style');
          clone.className = ctrl.tagName === 'SELECT' ? 'fi-ta-filter-select' : 'fi-ta-filter-input';
          clone.value = ctrl.value;

          fieldWrap.appendChild(lbl);
          fieldWrap.appendChild(clone);
          filterBody.appendChild(fieldWrap);
          syncedServerControls.push({ orig: ctrl, proxy: clone });
        });
      }

      // If no server select/date controls exist, build smart client-side column filter(s)
      if (syncedServerControls.length === 0) {
        var headerCells = Array.from(headRow.cells);
        headerCells.forEach(function (th, colIdx) {
          if (colIdx === 0 || clientColumnFilters.length >= 2) return;
          var hText = (th.textContent || '').replace(/\s+/g, ' ').trim();
          if (!hText || /^(no|#|urutan|aksi|nama|guru|pengguna|tanggal|materi|keterangan)/i.test(hText)) return;

          var uniqVals = [];
          rowMeta.forEach(function (m) {
            var cell = m.tr.cells[colIdx];
            if (!cell) return;
            var val = (cell.textContent || '').replace(/\s+/g, ' ').trim();
            if (val && val !== '-' && val.length <= 32 && uniqVals.indexOf(val) === -1) {
              uniqVals.push(val);
            }
          });

          if (uniqVals.length >= 2 && uniqVals.length <= 12) {
            var fieldWrap = document.createElement('div');
            fieldWrap.className = 'fi-ta-filter-field';
            var lbl = document.createElement('label');
            lbl.className = 'fi-ta-filter-label';
            lbl.textContent = hText;

            var sel = document.createElement('select');
            sel.className = 'fi-ta-filter-select';
            var optAll = document.createElement('option');
            optAll.value = '';
            optAll.textContent = 'Semua ' + hText;
            sel.appendChild(optAll);

            uniqVals.sort().forEach(function (v) {
              var o = document.createElement('option');
              o.value = v.toLowerCase();
              o.textContent = v;
              sel.appendChild(o);
            });

            fieldWrap.appendChild(lbl);
            fieldWrap.appendChild(sel);
            filterBody.appendChild(fieldWrap);
            clientColumnFilters.push({ colIdx: colIdx, select: sel, appliedValue: '' });
          }
        });

        // Always ensure at least 1 filter control exists in popover
        if (clientColumnFilters.length === 0) {
          var fallbackWrap = document.createElement('div');
          fallbackWrap.className = 'fi-ta-filter-field';
          fallbackWrap.innerHTML =
            '<label class="fi-ta-filter-label">Kata Kunci Spesifik</label>' +
            '<input type="text" class="fi-ta-filter-input" data-fi-keyword-filter placeholder="Filter teks kolom...">';
          filterBody.appendChild(fallbackWrap);
        }
      }

      var keywordFilterInput = filterBody.querySelector('[data-fi-keyword-filter]');
      var appliedKeywordFilter = '';

      function updateFilterBadge() {
        var activeCount = 0;
        syncedServerControls.forEach(function (pair) {
          var v = (pair.orig.value || '').trim();
          if (v !== '' && v.toLowerCase() !== 'all') activeCount++;
        });
        clientColumnFilters.forEach(function (cf) {
          if (cf.appliedValue !== '') activeCount++;
        });
        if (appliedKeywordFilter !== '') activeCount++;

        filterBadge.textContent = String(activeCount);
        filterBadge.classList.toggle('has-active', activeCount > 0);
        filterBtn.classList.toggle('has-filters', activeCount > 0);
      }
      updateFilterBadge();

      // Hide docked original filter bar cleanly while keeping its <form> in DOM for submission
      if (filterContainer) {
        filterContainer.classList.add('fi-filter-docked');
      }

      // Column Customizer Popover (Gambar Referensi Filament v4)
      var colWrap = document.createElement('div');
      colWrap.className = 'fi-ta-col-wrap';
      colWrap.innerHTML =
        '<button type="button" class="fi-ta-col-btn" title="Sesuaikan Kolom" aria-label="Sesuaikan Kolom">' +
          '<svg viewBox="0 0 20 20" fill="currentColor">' +
            '<rect x="3" y="3" width="3.2" height="14" rx="1.6" />' +
            '<rect x="8.4" y="3" width="3.2" height="14" rx="1.6" />' +
            '<rect x="13.8" y="3" width="3.2" height="14" rx="1.6" />' +
          '</svg>' +
        '</button>' +
        '<div class="fi-ta-col-popover">' +
          '<div class="fi-ta-col-head">' +
            '<span class="fi-ta-col-title">Kolom</span>' +
            '<button type="button" class="fi-ta-col-reset">Reset</button>' +
          '</div>' +
          '<div class="fi-ta-col-search-wrap">' +
            '<input type="text" class="fi-ta-col-search" placeholder="Cari nama kolom...">' +
          '</div>' +
          '<div class="fi-ta-col-list"></div>' +
        '</div>';

      var colBtn = colWrap.querySelector('.fi-ta-col-btn');
      var colPopover = colWrap.querySelector('.fi-ta-col-popover');
      var colSearchInput = colWrap.querySelector('.fi-ta-col-search');
      var colListEl = colWrap.querySelector('.fi-ta-col-list');
      var colResetBtn = colWrap.querySelector('.fi-ta-col-reset');

      toolbarRight.appendChild(searchWrap);
      toolbarRight.appendChild(filterWrap);
      toolbarRight.appendChild(colWrap);
      toolbar.appendChild(toolbarLeft);
      toolbar.appendChild(toolbarRight);

      // Selection Indicator Strip (Gambar 5: X dipilih | Pilih semua Z | Batalkan pilihan)
      var selectionBar = document.createElement('div');
      selectionBar.className = 'fi-ta-selection-bar';
      selectionBar.innerHTML =
        '<span class="fi-ta-selection-count">0 data dipilih</span>' +
        '<div class="fi-ta-selection-links">' +
          '<button type="button" class="fi-ta-link-select-all">Pilih semua</button>' +
          '<button type="button" class="fi-ta-link-deselect">Batalkan pilihan</button>' +
        '</div>';

      var selCountLabel = selectionBar.querySelector('.fi-ta-selection-count');
      var btnSelectAllFiltered = selectionBar.querySelector('.fi-ta-link-select-all');
      var btnDeselectAll = selectionBar.querySelector('.fi-ta-link-deselect');

      card.insertBefore(selectionBar, card.firstChild);
      card.insertBefore(toolbar, selectionBar);

      // Client-side Pagination Footer (when no Laravel server-side pagination is present)
      var hasServerPages = !!desktopServerPagination;
      var currentPage = 1;
      var perPage = 10;
      var footerInfoEl = null;
      var perPageSelect = null;
      var pagesWrapEl = null;

      if (desktopServerPagination) {
        desktopServerPagination.classList.add('fi-ta-footer');
        desktopServerPagination.style.padding = '12px 16px';
      } else {
        var footer = document.createElement('div');
        footer.className = 'fi-ta-footer';
        footer.innerHTML =
          '<div class="fi-ta-footer-info">Menampilkan 0 data</div>' +
          '<div class="fi-ta-perpage">' +
            '<span class="fi-ta-perpage-label">Per halaman</span>' +
            '<select class="fi-ta-perpage-select" aria-label="Per halaman">' +
              '<option value="10" selected>10</option>' +
              '<option value="25">25</option>' +
              '<option value="50">50</option>' +
              '<option value="9999">Semua</option>' +
            '</select>' +
          '</div>' +
          '<div class="fi-ta-pages"></div>';
        card.appendChild(footer);
        footerInfoEl = footer.querySelector('.fi-ta-footer-info');
        perPageSelect = footer.querySelector('.fi-ta-perpage-select');
        pagesWrapEl = footer.querySelector('.fi-ta-pages');

        perPageSelect.addEventListener('change', function () {
          perPage = parseInt(perPageSelect.value, 10) || 10;
          currentPage = 1;
          renderTableState();
        });
      }

      // Dynamic client-side empty search row
      var noMatchRow = document.createElement('tr');
      noMatchRow.style.display = 'none';
      var noMatchTd = document.createElement('td');
      noMatchTd.colSpan = headRow.cells.length;
      noMatchTd.style.cssText = 'padding:36px 16px; text-align:center; color:#6B7280; font-size:13.5px;';
      noMatchTd.textContent = 'Tidak ada data yang cocok dengan pencarian atau filter.';
      noMatchRow.appendChild(noMatchTd);
      if (dataRows.length > 0) {
        tbody.appendChild(noMatchRow);
      }

      // Column Customizer Logic & Persistence (Filament v4)
      var tableStorageKey = 'fi_cols_' + (table.id || (card.id ? card.id : window.location.pathname.replace(/[^a-zA-Z0-9_-]/g, '_')));
      var savedCols = {};
      try {
        savedCols = JSON.parse(localStorage.getItem(tableStorageKey) || '{}');
      } catch (e) {
        savedCols = {};
      }

      function updateColspans() {
        var visibleCount = 0;
        Array.from(headRow.cells).forEach(function (th) {
          if (th.style.display !== 'none') visibleCount++;
        });
        if (noMatchTd) noMatchTd.colSpan = Math.max(1, visibleCount);
        emptyRows.forEach(function (er) {
          if (er.cells[0]) er.cells[0].colSpan = Math.max(1, visibleCount);
        });
      }

      function applyColVisibility(colIdx, isVisible) {
        if (headRow && headRow.cells[colIdx]) {
          headRow.cells[colIdx].style.display = isVisible ? '' : 'none';
        }
        dataRows.forEach(function (tr) {
          if (tr.cells[colIdx]) {
            tr.cells[colIdx].style.display = isVisible ? '' : 'none';
          }
        });
        updateColspans();
      }

      var hasHiddenCols = false;
      Array.from(headRow.cells).forEach(function (th, idx) {
        if (idx === 0 || th.classList.contains('fi-ta-th-check')) return;
        var colTitle = (th.querySelector('span') ? th.querySelector('span').textContent : th.textContent || '').replace(/\s+/g, ' ').trim() || ('Kolom ' + idx);

        var defaultVisible = !th.hasAttribute('data-col-hidden') && th.getAttribute('data-col-hidden') !== 'true';
        var isVisible = savedCols[idx] !== undefined ? !!savedCols[idx] : defaultVisible;
        if (!isVisible) hasHiddenCols = true;

        applyColVisibility(idx, isVisible);

        var itemLabel = document.createElement('label');
        itemLabel.className = 'fi-ta-col-item';
        itemLabel.innerHTML =
          '<input type="checkbox" class="fi-ta-checkbox fi-ta-col-check" data-col-idx="' + idx + '"' + (isVisible ? ' checked' : '') + '>' +
          '<span class="fi-ta-col-item-text">' + colTitle.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>';

        var colCb = itemLabel.querySelector('input');
        colCb.addEventListener('change', function () {
          var checked = this.checked;
          savedCols[idx] = checked;
          try {
            localStorage.setItem(tableStorageKey, JSON.stringify(savedCols));
          } catch (e) {}
          applyColVisibility(idx, checked);
          var anyHidden = Array.from(headRow.cells).some(function (cell, i) {
            return i > 0 && cell.style.display === 'none';
          });
          colBtn.classList.toggle('has-hidden-cols', anyHidden);
        });

        colListEl.appendChild(itemLabel);
      });

      // Quick filter search inside column customizer popover
      if (colSearchInput) {
        colSearchInput.addEventListener('input', function () {
          var q = (colSearchInput.value || '').trim().toLowerCase();
          colListEl.querySelectorAll('.fi-ta-col-item').forEach(function (item) {
            var txt = (item.querySelector('.fi-ta-col-item-text') ? item.querySelector('.fi-ta-col-item-text').textContent : '').toLowerCase();
            item.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
          });
        });
      }

      colBtn.classList.toggle('has-hidden-cols', hasHiddenCols);
      updateColspans();

      colResetBtn.addEventListener('click', function () {
        Array.from(headRow.cells).forEach(function (th, idx) {
          if (idx === 0) return;
          var defVis = !th.hasAttribute('data-col-hidden') || th.getAttribute('data-col-hidden') !== 'true';
          applyColVisibility(idx, defVis);
        });
        colListEl.querySelectorAll('.fi-ta-col-check').forEach(function (cb) {
          var idx = parseInt(cb.dataset.colIdx, 10);
          var th = headRow.cells[idx];
          cb.checked = th ? (!th.hasAttribute('data-col-hidden') || th.getAttribute('data-col-hidden') !== 'true') : true;
        });
        if (colSearchInput) {
          colSearchInput.value = '';
          colListEl.querySelectorAll('.fi-ta-col-item').forEach(function (item) { item.style.display = ''; });
        }
        savedCols = {};
        try {
          localStorage.removeItem(tableStorageKey);
        } catch (e) {}
        var anyHidden = Array.from(headRow.cells).some(function (cell, i) {
          return i > 0 && cell.style.display === 'none';
        });
        colBtn.classList.toggle('has-hidden-cols', anyHidden);
      });

      colBtn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        bulkMenu.classList.remove('is-open');
        filterPopover.classList.remove('is-open');
        colPopover.classList.toggle('is-open');
        if (colPopover.classList.contains('is-open') && colSearchInput) {
          setTimeout(function () { colSearchInput.focus(); }, 50);
        }
      });

      colPopover.addEventListener('click', function (ev) {
        ev.stopPropagation();
      });

      function getFilteredMeta() {
        var q = (searchInput.value || '').trim().toLowerCase();
        // If search matches initial server-filtered query and user hasn't typed anything new, don't double-exclude rows whose server match was on a hidden field
        var effectiveQ = (origSearchInput && q === initialSearchVal.trim().toLowerCase()) ? '' : q;

        return rowMeta.filter(function (m) {
          if (effectiveQ && m.searchText.indexOf(effectiveQ) === -1) return false;
          if (appliedKeywordFilter && m.searchText.indexOf(appliedKeywordFilter) === -1) return false;

          for (var i = 0; i < clientColumnFilters.length; i++) {
            var cf = clientColumnFilters[i];
            if (cf.appliedValue) {
              var cell = m.tr.cells[cf.colIdx];
              var cText = cell ? (cell.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase() : '';
              if (cText !== cf.appliedValue) return false;
            }
          }
          return true;
        });
      }

      function getPageMeta(filtered) {
        if (hasServerPages) return filtered;
        var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        if (currentPage > totalPages) currentPage = totalPages;
        var start = (currentPage - 1) * perPage;
        return filtered.slice(start, start + perPage);
      }

      function renderBulkMenu(selectedCount) {
        bulkMenu.innerHTML = '';
        var selectedItems = rowMeta.filter(function (m) { return m.selected; });
        if (selectedItems.length === 0) return;

        if (selectedCount === 1) {
          var selMeta = selectedItems[0];
          var tr = selMeta.tr;
          var viewLnk = tr.querySelector('a.action-btn-view, a.btn-view, a[title*="Lihat"], a[href*="show"], a[href*="cetak"]');
          var editLnk = tr.querySelector('a.action-btn-edit, a.btn-edit, a.action-btn-koreksi, a[title*="Edit"], a[href*="edit"]');
          var rfidBtn = tr.querySelector('button.btn-rfid-pair, button[data-uid], button[title*="RFID"]');
          var delBtn = tr.querySelector('.action-btn-delete, .btn-delete, button.js-swal-confirm');

          // 1. Lihat Formulir Siswa
          if (viewLnk) {
            var aView = document.createElement('a');
            aView.className = 'fi-ta-bulk-item';
            aView.href = viewLnk.href;
            aView.innerHTML =
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>' +
                '<circle cx="12" cy="12" r="3"></circle>' +
              '</svg>' +
              '<span>Lihat Formulir Siswa</span>';
            bulkMenu.appendChild(aView);
          }

          // 2. Edit Data Siswa
          if (editLnk) {
            var aEdit = document.createElement('a');
            aEdit.className = 'fi-ta-bulk-item';
            aEdit.href = editLnk.href;
            aEdit.innerHTML =
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>' +
                '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>' +
              '</svg>' +
              '<span>Edit Data Siswa</span>';
            bulkMenu.appendChild(aEdit);
          }

          // 3. Pairing Kartu RFID
          if (rfidBtn) {
            var bRfid = document.createElement('button');
            bRfid.type = 'button';
            bRfid.className = 'fi-ta-bulk-item';
            bRfid.innerHTML =
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<rect x="2" y="5" width="20" height="14" rx="2"></rect>' +
                '<line x1="2" y1="10" x2="22" y2="10"></line>' +
              '</svg>' +
              '<span>Pairing Kartu RFID</span>';
            bRfid.addEventListener('click', function () {
              bulkMenu.classList.remove('is-open');
              rfidBtn.click();
            });
            bulkMenu.appendChild(bRfid);
          }

          if (viewLnk || editLnk || rfidBtn) {
            var div1 = document.createElement('div');
            div1.className = 'fi-ta-bulk-divider';
            bulkMenu.appendChild(div1);
          }

          // 4. Hapus Siswa
          if (delBtn || selMeta.deleteForm) {
            var bDelOne = document.createElement('button');
            bDelOne.type = 'button';
            bDelOne.className = 'fi-ta-bulk-item is-danger';
            bDelOne.innerHTML =
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<polyline points="3 6 5 6 21 6"></polyline>' +
                '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>' +
              '</svg>' +
              '<span>Hapus Siswa</span>';
            bDelOne.addEventListener('click', function () {
              bulkMenu.classList.remove('is-open');
              if (delBtn) {
                delBtn.click();
              } else if (selMeta.deleteForm) {
                selMeta.deleteForm.submit();
              }
            });
            bulkMenu.appendChild(bDelOne);
          }
        } else {
          // Multiple rows selected
          var deletableSelected = selectedItems.filter(function (m) { return m.deleteForm; });
          if (deletableSelected.length > 0) {
            var bDelAll = document.createElement('button');
            bDelAll.type = 'button';
            bDelAll.className = 'fi-ta-bulk-item is-danger';
            bDelAll.innerHTML =
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<polyline points="3 6 5 6 21 6"></polyline>' +
                '<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>' +
              '</svg>' +
              '<span>Hapus terpilih (' + selectedCount + ')</span>';
            bDelAll.addEventListener('click', function () {
              bulkMenu.classList.remove('is-open');
              window.showFiConfirmModal({
                title: 'Hapus data terpilih',
                description: 'Apakah Anda yakin ingin menghapus ' + deletableSelected.length + ' data yang dipilih? Tindakan ini tidak dapat dibatalkan.',
                confirmText: 'Hapus',
                cancelText: 'Batal',
                variant: 'danger',
                onConfirm: function () {
                  return deletableSelected.reduce(function (chain, item) {
                    return chain.then(function () {
                      return fetch(item.deleteForm.action, {
                        method: 'POST',
                        body: new FormData(item.deleteForm),
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                      });
                    });
                  }, Promise.resolve()).then(function () {
                    window.location.reload();
                  });
                }
              });
            });
            bulkMenu.appendChild(bDelAll);
          }
        }

        // Ekspor CSV Terpilih (always available)
        var bCsv = document.createElement('button');
        bCsv.type = 'button';
        bCsv.className = 'fi-ta-bulk-item is-primary';
        bCsv.innerHTML =
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>' +
            '<polyline points="7 10 12 15 17 10"></polyline>' +
            '<line x1="12" y1="15" x2="12" y2="3"></line>' +
          '</svg>' +
          '<span>Ekspor CSV terpilih</span>';
        bCsv.addEventListener('click', function () {
          bulkMenu.classList.remove('is-open');
          var selTrs = selectedItems.map(function (m) { return m.tr; });
          if (selTrs.length) downloadSelectedCsv(table, selTrs);
        });
        bulkMenu.appendChild(bCsv);

        var div2 = document.createElement('div');
        div2.className = 'fi-ta-bulk-divider';
        bulkMenu.appendChild(div2);

        // Batalkan Pilihan
        var bCancel = document.createElement('button');
        bCancel.type = 'button';
        bCancel.className = 'fi-ta-bulk-item';
        bCancel.innerHTML =
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<line x1="18" y1="6" x2="6" y2="18"></line>' +
            '<line x1="6" y1="6" x2="18" y2="18"></line>' +
          '</svg>' +
          '<span>Batalkan pilihan</span>';
        bCancel.addEventListener('click', function () {
          bulkMenu.classList.remove('is-open');
          clearAllSelections();
        });
        bulkMenu.appendChild(bCancel);
      }

      function syncSelectionUI(filtered, pageItems) {
        var selectedCount = 0;
        rowMeta.forEach(function (m) {
          if (m.selected) selectedCount++;
          m.cb.checked = m.selected;
          m.tr.classList.toggle('is-selected', m.selected);
          if (m.mCb) m.mCb.checked = m.selected;
          if (m.mCard) m.mCard.classList.toggle('is-selected', m.selected);
        });

        var allPageChecked = pageItems.length > 0 && pageItems.every(function (m) { return m.selected; });
        var somePageChecked = pageItems.some(function (m) { return m.selected; });
        masterCheck.checked = allPageChecked;
        masterCheck.indeterminate = !allPageChecked && somePageChecked;

        var countBadge = bulkBtn.querySelector('.fi-ta-bulk-count');
        if (selectedCount > 0) {
          bulkBtn.disabled = false;
          bulkBtn.classList.remove('is-disabled');
          bulkBtn.classList.add('is-active-selection');
          countBadge.style.display = 'inline-flex';
          countBadge.textContent = String(selectedCount);
          selectionBar.classList.add('is-visible');
          selCountLabel.textContent = selectedCount + ' data dipilih';
          btnSelectAllFiltered.textContent = 'Pilih semua ' + filtered.length;
          renderBulkMenu(selectedCount);
        } else {
          bulkBtn.disabled = true;
          bulkBtn.classList.add('is-disabled');
          bulkBtn.classList.remove('is-active-selection');
          countBadge.style.display = 'none';
          bulkMenu.classList.remove('is-open');
          selectionBar.classList.remove('is-visible');
          bulkMenu.innerHTML = '';
        }
      }

      function renderPaginationControls(totalFiltered) {
        if (hasServerPages || !footerInfoEl) return;

        if (totalFiltered === 0) {
          footerInfoEl.textContent = 'Menampilkan 0 data';
          pagesWrapEl.style.display = 'none';
          return;
        }

        var totalPages = Math.max(1, Math.ceil(totalFiltered / perPage));
        if (currentPage > totalPages) currentPage = totalPages;
        var from = (currentPage - 1) * perPage + 1;
        var to = Math.min(currentPage * perPage, totalFiltered);
        footerInfoEl.textContent = 'Menampilkan ' + from + ' sampai ' + to + ' dari ' + totalFiltered + ' data';

        if (totalPages <= 1) {
          pagesWrapEl.style.display = 'none';
          pagesWrapEl.innerHTML = '';
          return;
        }

        pagesWrapEl.style.display = 'inline-flex';
        pagesWrapEl.innerHTML = '';

        function addPageBtn(label, targetPage, disabled, active) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'fi-ta-page-btn' + (active ? ' is-active' : '');
          b.disabled = !!disabled;
          b.textContent = label;
          if (!disabled && !active) {
            b.addEventListener('click', function () {
              currentPage = targetPage;
              renderTableState();
            });
          }
          pagesWrapEl.appendChild(b);
        }

        addPageBtn('‹', currentPage - 1, currentPage <= 1, false);
        for (var p = 1; p <= totalPages; p++) {
          if (totalPages <= 7 || p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1) {
            addPageBtn(String(p), p, false, p === currentPage);
          } else if (pagesWrapEl.lastChild && pagesWrapEl.lastChild.textContent !== '…') {
            addPageBtn('…', p, true, false);
          }
        }
        addPageBtn('›', currentPage + 1, currentPage >= totalPages, false);
      }

      function renderTableState() {
        var filtered = getFilteredMeta();
        var pageItems = getPageMeta(filtered);
        var visibleSet = new Set(pageItems);

        rowMeta.forEach(function (m) {
          var show = visibleSet.has(m);
          m.tr.style.display = show ? '' : 'none';
          if (m.mCard) m.mCard.style.display = show ? '' : 'none';
        });

        if (dataRows.length > 0) {
          noMatchRow.style.display = filtered.length === 0 ? '' : 'none';
        }

        renderPaginationControls(filtered.length);
        syncSelectionUI(filtered, pageItems);
      }

      // Wire row & mobile checkboxes + click to select
      rowMeta.forEach(function (m) {
        m.cb.addEventListener('change', function () {
          m.selected = m.cb.checked;
          var filtered = getFilteredMeta();
          syncSelectionUI(filtered, getPageMeta(filtered));
        });
        m.tr.addEventListener('click', function (ev) {
          if (ev.target.closest('input, button, a, label, select, textarea')) return;
          m.cb.checked = !m.cb.checked;
          m.selected = m.cb.checked;
          var filtered = getFilteredMeta();
          syncSelectionUI(filtered, getPageMeta(filtered));
        });
        if (m.mCb) {
          m.mCb.addEventListener('change', function () {
            m.selected = m.mCb.checked;
            var filtered = getFilteredMeta();
            syncSelectionUI(filtered, getPageMeta(filtered));
          });
        }
      });

      masterCheck.addEventListener('change', function () {
        var filtered = getFilteredMeta();
        var pageItems = getPageMeta(filtered);
        pageItems.forEach(function (m) {
          m.selected = masterCheck.checked;
        });
        syncSelectionUI(filtered, pageItems);
      });

      btnSelectAllFiltered.addEventListener('click', function () {
        var filtered = getFilteredMeta();
        filtered.forEach(function (m) { m.selected = true; });
        syncSelectionUI(filtered, getPageMeta(filtered));
      });

      function clearAllSelections() {
        rowMeta.forEach(function (m) { m.selected = false; });
        var filtered = getFilteredMeta();
        syncSelectionUI(filtered, getPageMeta(filtered));
      }

      btnDeselectAll.addEventListener('click', clearAllSelections);

      // Wire Bulk Actions Menu toggle & outside click
      bulkBtn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        if (bulkBtn.disabled) return;
        filterPopover.classList.remove('is-open');
        colPopover.classList.remove('is-open');
        bulkMenu.classList.toggle('is-open');
      });

      // Wire Search Input (live client filter + Enter submits server search if present)
      searchInput.addEventListener('input', function () {
        searchClear.classList.toggle('is-visible', searchInput.value.length > 0);
        if (origSearchInput) origSearchInput.value = searchInput.value;
        currentPage = 1;
        renderTableState();
      });

      searchInput.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' && origForm) {
          ev.preventDefault();
          if (origSearchInput) origSearchInput.value = searchInput.value;
          origForm.submit();
        }
      });

      searchClear.addEventListener('click', function () {
        searchInput.value = '';
        searchClear.classList.remove('is-visible');
        if (origSearchInput && initialSearchVal && origForm) {
          origSearchInput.value = '';
          origForm.submit();
          return;
        }
        currentPage = 1;
        renderTableState();
      });

      // Wire Filter Funnel Popover
      filterBtn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        bulkMenu.classList.remove('is-open');
        colPopover.classList.remove('is-open');
        filterPopover.classList.toggle('is-open');
      });

      filterPopover.addEventListener('click', function (ev) {
        ev.stopPropagation();
      });

      filterApplyBtn.addEventListener('click', function () {
        if (origForm && syncedServerControls.length > 0) {
          syncedServerControls.forEach(function (pair) {
            pair.orig.value = pair.proxy.value;
          });
          if (origSearchInput) origSearchInput.value = searchInput.value;
          origForm.submit();
          return;
        }

        clientColumnFilters.forEach(function (cf) {
          cf.appliedValue = (cf.select.value || '').trim().toLowerCase();
        });
        if (keywordFilterInput) {
          appliedKeywordFilter = (keywordFilterInput.value || '').trim().toLowerCase();
        }
        currentPage = 1;
        updateFilterBadge();
        filterPopover.classList.remove('is-open');
        renderTableState();
      });

      filterResetBtn.addEventListener('click', function () {
        if (origResetLink && origResetLink.href) {
          window.location.href = origResetLink.href;
          return;
        }
        if (origForm && syncedServerControls.length > 0) {
          syncedServerControls.forEach(function (pair) {
            pair.proxy.value = '';
            pair.orig.value = '';
          });
          if (origSearchInput) origSearchInput.value = '';
          origForm.submit();
          return;
        }
        clientColumnFilters.forEach(function (cf) {
          cf.select.value = '';
          cf.appliedValue = '';
        });
        if (keywordFilterInput) {
          keywordFilterInput.value = '';
          appliedKeywordFilter = '';
        }
        searchInput.value = '';
        searchClear.classList.remove('is-visible');
        currentPage = 1;
        updateFilterBadge();
        filterPopover.classList.remove('is-open');
        renderTableState();
      });

      document.addEventListener('click', function (ev) {
        if (!bulkWrap.contains(ev.target)) bulkMenu.classList.remove('is-open');
        if (!filterWrap.contains(ev.target)) filterPopover.classList.remove('is-open');
        if (!colWrap.contains(ev.target)) colPopover.classList.remove('is-open');
      });

      document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
          bulkMenu.classList.remove('is-open');
          filterPopover.classList.remove('is-open');
          colPopover.classList.remove('is-open');
        }
      });

      // Column Sorting (Gambar 3 Chevron Sort)
      var currentSortCol = -1;
      var currentSortAsc = true;
      Array.from(headRow.cells).forEach(function (th, colIdx) {
        if (colIdx === 0) return;
        var labelTxt = (th.textContent || '').replace(/\s+/g, ' ').trim();
        if (!labelTxt || /^aksi/i.test(labelTxt)) return;

        var sortBtn = document.createElement('button');
        sortBtn.type = 'button';
        sortBtn.className = 'fi-ta-th-sort';
        sortBtn.innerHTML =
          '<span>' + labelTxt + '</span>' +
          '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
            '<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />' +
          '</svg>';

        th.textContent = '';
        th.appendChild(sortBtn);

        sortBtn.addEventListener('click', function () {
          if (currentSortCol === colIdx) {
            currentSortAsc = !currentSortAsc;
          } else {
            currentSortCol = colIdx;
            currentSortAsc = true;
          }

          headRow.querySelectorAll('.fi-ta-th-sort').forEach(function (b) {
            b.classList.remove('is-sorted');
            var svg = b.querySelector('svg');
            if (svg) svg.style.transform = '';
          });
          sortBtn.classList.add('is-sorted');
          var activeSvg = sortBtn.querySelector('svg');
          if (activeSvg) activeSvg.style.transform = currentSortAsc ? 'rotate(180deg)' : 'rotate(0deg)';

          rowMeta.sort(function (a, b) {
            var aTxt = a.tr.cells[colIdx] ? (a.tr.cells[colIdx].textContent || '').trim() : '';
            var bTxt = b.tr.cells[colIdx] ? (b.tr.cells[colIdx].textContent || '').trim() : '';
            var aNum = parseFloat(aTxt.replace(/[^\d.-]/g, ''));
            var bNum = parseFloat(bTxt.replace(/[^\d.-]/g, ''));
            var cmp = (!isNaN(aNum) && !isNaN(bNum) && /^\d/.test(aTxt) && /^\d/.test(bTxt))
              ? (aNum - bNum)
              : aTxt.localeCompare(bTxt, 'id', { sensitivity: 'base' });
            return currentSortAsc ? cmp : -cmp;
          });

          rowMeta.forEach(function (m) {
            tbody.insertBefore(m.tr, noMatchRow);
            if (m.mCard && mobileList) mobileList.appendChild(m.mCard);
          });
          renderTableState();
        });
      });

      renderTableState();
    });
  }

  initFilamentTables();
});
