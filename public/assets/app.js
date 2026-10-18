/* Friends-SNCMS · Author: Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
(() => {
  const root = document.documentElement;
  try { const mode = localStorage.getItem('friends.mode'); if (['light','dark'].includes(mode)) root.dataset.mode = mode; } catch {}
  document.querySelector('.mode-button')?.addEventListener('click', () => {
    const dark = root.dataset.mode ? root.dataset.mode === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    root.dataset.mode = dark ? 'light' : 'dark';
    try { localStorage.setItem('friends.mode', root.dataset.mode); } catch {}
  });
  document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!confirm(form.dataset.confirm)) event.preventDefault();
  }));
})();
