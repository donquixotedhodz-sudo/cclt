document.addEventListener('DOMContentLoaded', () => {
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
});