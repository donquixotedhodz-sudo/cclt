const initApp = () => {
  // Confirm delete actions
  document.querySelectorAll('[data-confirm]')?.forEach(el => {
    el.addEventListener('click', (e) => {
      const msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // Simple client-side search filter for tables
  const searchInputs = document.querySelectorAll('[data-table-filter]');
  searchInputs.forEach(input => {
    input.addEventListener('input', () => {
      const targetSelector = input.getAttribute('data-table-filter');
      const table = document.querySelector(targetSelector);
      const term = input.value.toLowerCase();
      if (!table) return;
      table.querySelectorAll('tbody tr').forEach(row => {
        const text = row.textContent?.toLowerCase() || '';
        row.style.display = text.includes(term) ? '' : 'none';
      });
    });
  });

  // Auto print when requested
  const autoPrint = document.body && document.body.getAttribute('data-auto-print') === 'true';
  if (autoPrint) {
    setTimeout(() => window.print(), 300);
  }

  // Toggle Login/Register panels on index page
  const btnShowRegister = document.getElementById('btnShowRegister');
  const btnShowLogin = document.getElementById('btnShowLogin');
  const btnBorrowerBackToLogin = document.getElementById('btnBorrowerBackToLogin');
  const btnShowBorrowerLogin = document.getElementById('btnShowBorrowerLogin');
  const loginPanel = document.getElementById('loginPanel');
  const registerPanel = document.getElementById('registerPanel');
  const borrowerLoginPanel = document.getElementById('borrowerLoginPanel');
  btnShowRegister?.addEventListener('click', () => {
    loginPanel?.classList.add('d-none');
    registerPanel?.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  btnShowLogin?.addEventListener('click', () => {
    registerPanel?.classList.add('d-none');
    loginPanel?.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  btnShowBorrowerLogin?.addEventListener('click', () => {
    loginPanel?.classList.add('d-none');
    registerPanel?.classList.add('d-none');
    borrowerLoginPanel?.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  btnBorrowerBackToLogin?.addEventListener('click', () => {
    borrowerLoginPanel?.classList.add('d-none');
    loginPanel?.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  // Show/hide password toggle icon appears when input has value
  const setupPasswordToggles = () => {
    const containers = document.querySelectorAll('.input-with-toggle');
    containers.forEach(container => {
      const input = container.querySelector('input[type="password"]');
      const toggle = container.querySelector('.password-toggle');
      if (!input || !toggle) return;

      const updateVisibility = () => {
        if (input.value && input.value.length > 0) {
          toggle.classList.remove('d-none');
        } else {
          toggle.classList.add('d-none');
          // Reset to hidden state when cleared
          if (input.type === 'text') {
            input.type = 'password';
            const icon = toggle.querySelector('i');
            if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
            toggle.setAttribute('aria-label', 'Show password');
          }
        }
      };

      input.addEventListener('input', updateVisibility);
      updateVisibility();

      toggle.addEventListener('click', () => {
        const icon = toggle.querySelector('i');
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
          toggle.setAttribute('aria-label', 'Hide password');
        } else {
          input.type = 'password';
          if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
          toggle.setAttribute('aria-label', 'Show password');
        }
      });
    });
  };

  setupPasswordToggles();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}