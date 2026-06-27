/**
 * E-Gouvernance État Civil — JavaScript principal
 * Gestion des interactions UI, formulaires, notifications, sidebar
 */

'use strict';

// ============================================================
// INITIALISATION
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
  App.init();
});

const App = {
  init() {
    this.initSidebar();
    this.initFlashMessages();
    this.initFormValidation();
    this.initFileUploads();
    this.initDataTables();
    this.initTooltips();
    this.initCounters();
    this.initSearch();
    this.initConfirmDialogs();
    this.initNotifications();
    this.initPrintButtons();
    console.log('[E-Gouvernance] Application initialisée v1.0.0');
  },

  // ============================================================
  // SIDEBAR MOBILE
  // ============================================================
  initSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar) return;

    const openSidebar = () => {
      sidebar.classList.add('show');
      if (overlay) overlay.classList.add('show');
      document.body.style.overflow = 'hidden';
    };

    const closeSidebar = () => {
      sidebar.classList.remove('show');
      if (overlay) overlay.classList.remove('show');
      document.body.style.overflow = '';
    };

    toggle?.addEventListener('click', openSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // Fermer avec Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeSidebar();
    });

    // Marquer le lien actif
    const currentPath = window.location.pathname;
    sidebar.querySelectorAll('.nav-link').forEach(link => {
      if (link.getAttribute('href') === currentPath ||
          currentPath.includes(link.getAttribute('href'))) {
        link.classList.add('active');
      }
    });
  },

  // ============================================================
  // MESSAGES FLASH
  // ============================================================
  initFlashMessages() {
    document.querySelectorAll('.flash-alert').forEach(alert => {
      // Auto-fermeture après 5 secondes
      setTimeout(() => {
        alert.style.animation = 'slideOutRight .35s ease forwards';
        setTimeout(() => alert.remove(), 350);
      }, 5000);

      alert.querySelector('.btn-close')?.addEventListener('click', () => {
        alert.style.animation = 'slideOutRight .35s ease forwards';
        setTimeout(() => alert.remove(), 350);
      });
    });
  },

  showFlash(type, message) {
    const icons = {
      success: 'fas fa-check-circle',
      danger:  'fas fa-exclamation-circle',
      warning: 'fas fa-exclamation-triangle',
      info:    'fas fa-info-circle',
    };

    const div = document.createElement('div');
    div.className = `flash-alert alert alert-${type} alert-dismissible d-flex align-items-center gap-2`;
    div.innerHTML = `
      <i class="${icons[type] || icons.info}"></i>
      <div>${message}</div>
      <button type="button" class="btn-close" aria-label="Fermer"></button>
    `;
    document.body.appendChild(div);
    this.initFlashMessages();
  },

  // ============================================================
  // VALIDATION DES FORMULAIRES
  // ============================================================
  initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
      form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
          // Focus sur le premier champ invalide
          const firstInvalid = form.querySelector(':invalid');
          firstInvalid?.focus();
          firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
          // Afficher le spinner
          const btn = form.querySelector('[type="submit"]');
          if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement…';
          }
        }
        form.classList.add('was-validated');
      });
    });

    // Validation email en temps réel
    document.querySelectorAll('input[type="email"]').forEach(input => {
      input.addEventListener('blur', () => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (input.value && !emailRegex.test(input.value)) {
          input.setCustomValidity('Adresse email invalide.');
          input.reportValidity();
        } else {
          input.setCustomValidity('');
        }
      });
    });

    // Confirmation mot de passe
    const pass1 = document.getElementById('password');
    const pass2 = document.getElementById('password_confirm');
    if (pass1 && pass2) {
      const checkMatch = () => {
        if (pass2.value && pass1.value !== pass2.value) {
          pass2.setCustomValidity('Les mots de passe ne correspondent pas.');
        } else {
          pass2.setCustomValidity('');
        }
      };
      pass1.addEventListener('input', checkMatch);
      pass2.addEventListener('input', checkMatch);
    }

    // Indicateur force du mot de passe
    const passwordInput = document.getElementById('password');
    const strengthBar   = document.getElementById('passwordStrength');
    if (passwordInput && strengthBar) {
      passwordInput.addEventListener('input', () => {
        const val      = passwordInput.value;
        let strength   = 0;
        if (val.length >= 8)       strength++;
        if (/[A-Z]/.test(val))     strength++;
        if (/[0-9]/.test(val))     strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;

        const colors = ['', '#dc3545', '#ffc107', '#2196f3', '#2e7d32'];
        const labels = ['', 'Très faible', 'Faible', 'Moyen', 'Fort'];
        strengthBar.style.width      = (strength * 25) + '%';
        strengthBar.style.background = colors[strength];
        strengthBar.setAttribute('aria-valuenow', strength * 25);

        const label = document.getElementById('strengthLabel');
        if (label) label.textContent = labels[strength] || '';
      });
    }
  },

  // ============================================================
  // UPLOAD DE FICHIERS
  // ============================================================
  initFileUploads() {
    document.querySelectorAll('.upload-zone').forEach(zone => {
      const input = zone.querySelector('input[type="file"]') ||
                    document.getElementById(zone.dataset.input);
      if (!input) return;

      zone.addEventListener('click', () => input.click());

      zone.addEventListener('dragover', e => {
        e.preventDefault();
        zone.classList.add('drag-over');
      });

      zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));

      zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) {
          input.files = e.dataTransfer.files;
          this.updateUploadZone(zone, e.dataTransfer.files);
        }
      });

      input.addEventListener('change', () => {
        this.updateUploadZone(zone, input.files);
      });
    });
  },

  updateUploadZone(zone, files) {
    const info = zone.querySelector('.upload-info');
    if (!info || !files.length) return;

    const file   = files[0];
    const sizeMB = (file.size / 1048576).toFixed(2);
    info.innerHTML = `<i class="fas fa-file-check text-success me-2"></i><strong>${file.name}</strong> (${sizeMB} Mo)`;
    zone.style.borderColor = '#2e7d32';
    zone.style.background  = '#e8f5e9';
  },

  // ============================================================
  // DATATABLES (recherche + pagination locale)
  // ============================================================
  initDataTables() {
    document.querySelectorAll('[data-table]').forEach(table => {
      const searchInput = document.getElementById(table.dataset.search);
      if (!searchInput) return;

      searchInput.addEventListener('input', () => {
        const term  = searchInput.value.toLowerCase();
        const tbody = table.querySelector('tbody');
        tbody?.querySelectorAll('tr').forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(term) ? '' : 'none';
        });
      });
    });
  },

  // ============================================================
  // TOOLTIPS BOOTSTRAP
  // ============================================================
  initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
  },

  // ============================================================
  // COMPTEURS ANIMÉS
  // ============================================================
  initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el     = entry.target;
          const target = parseInt(el.dataset.counter, 10);
          let current  = 0;
          const step   = Math.ceil(target / 50);
          const timer  = setInterval(() => {
            current += step;
            if (current >= target) {
              current = target;
              clearInterval(timer);
            }
            el.textContent = current.toLocaleString('fr-FR');
          }, 30);
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
  },

  // ============================================================
  // RECHERCHE EN-TÊTE
  // ============================================================
  initSearch() {
    const searchForm = document.getElementById('headerSearch');
    if (!searchForm) return;

    searchForm.addEventListener('submit', e => {
      e.preventDefault();
      const q = searchForm.querySelector('input')?.value?.trim();
      if (q) window.location.href = `search.php?q=${encodeURIComponent(q)}`;
    });
  },

  // ============================================================
  // DIALOGUES DE CONFIRMATION
  // ============================================================
  initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
      el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm)) {
          e.preventDefault();
          return false;
        }
      });
    });
  },

  // ============================================================
  // NOTIFICATIONS EN TEMPS RÉEL (polling léger)
  // ============================================================
  initNotifications() {
    const badge = document.getElementById('notifBadge');
    if (!badge) return;

    const checkNotifs = () => {
      fetch('api/notifications.php?action=count', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
      .then(r => r.json())
      .then(data => {
        if (data.count > 0) {
          badge.textContent = data.count;
          badge.style.display = '';
        } else {
          badge.style.display = 'none';
        }
      })
      .catch(() => {}); // Silencieux si pas connecté
    };

    checkNotifs();
    setInterval(checkNotifs, 60000); // Vérifier toutes les minutes
  },

  // ============================================================
  // IMPRESSION
  // ============================================================
  initPrintButtons() {
    document.querySelectorAll('[data-print]').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.print);
        if (target) {
          const original = document.body.innerHTML;
          document.body.innerHTML = target.innerHTML;
          window.print();
          document.body.innerHTML = original;
          App.init(); // Réinitialiser après impression
        } else {
          window.print();
        }
      });
    });
  },
};

// ============================================================
// UTILITAIRES GLOBAUX
// ============================================================

/** Copier du texte dans le presse-papiers */
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    App.showFlash('success', 'Copié dans le presse-papiers !');
  }).catch(() => {
    App.showFlash('danger', 'Impossible de copier.');
  });
}

/** Formater une date en français */
function formatDateFr(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/** Valider un numéro de téléphone camerounais */
function validatePhone(phone) {
  return /^(\+237|00237)?[26789]\d{8}$/.test(phone.replace(/\s/g, ''));
}

// Styles CSS animation supplémentaires ajoutés dynamiquement
(function addDynamicStyles() {
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideOutRight {
      from { opacity:1; transform:translateX(0); }
      to   { opacity:0; transform:translateX(100%); }
    }
    .drag-over {
      border-color: var(--bleu-accent) !important;
      background: #e3f2fd !important;
    }
    .sidebar-overlay {
      position:fixed; inset:0; background:rgba(0,0,0,.5);
      z-index:1029; display:none;
    }
    .sidebar-overlay.show { display:block; }
  `;
  document.head.appendChild(style);
})();
