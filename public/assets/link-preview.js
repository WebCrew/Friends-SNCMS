/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
export function firstLink(text) {
  for (let candidate of text.match(/https?:\/\/[^\s<>"']+/giu) || []) {
    candidate = candidate.replace(/[.,!?;:]+$/u, '');
    for (const [close, open] of [[')', '('], [']', '['], ['}', '{']]) {
      while (candidate.endsWith(close) && candidate.split(close).length > candidate.split(open).length) candidate = candidate.slice(0, -1);
    }
    candidate = candidate.replace(/^https?:\/\//iu, scheme => scheme.toLowerCase());
    try {
      const url = new URL(candidate);
      if (candidate.length <= 2048 && !/[\x00-\x20\x7f\\]/u.test(candidate) && !url.username && !url.password && ['http:', 'https:'].includes(url.protocol) && url.hostname) return candidate;
    } catch {}
  }
  return '';
}

if (typeof document !== 'undefined') document.querySelectorAll('.feed-composer').forEach(form => {
  const box = form.querySelector('.auto-link-preview');
  if (!box) return;
  const input = form.querySelector('textarea[name=body]'), result = box.querySelector('.link-result'), status = box.querySelector('.preview-status');
  const cache = new Map();
  let selected = '', revision = 0, timer, controller;
  function render(data) {
    const card = document.createElement('div'); card.className = 'link-card';
    const domain = document.createElement('small'); domain.textContent = new URL(data.url).hostname;
    const title = document.createElement('strong'); title.textContent = data.title;
    const description = document.createElement('p'); description.textContent = data.description || '';
    card.append(domain, title); if (description.textContent) card.append(description);
    result.replaceChildren(card);
  }
  async function load(url, current) {
    controller = new AbortController();
    try {
      const body = new URLSearchParams({csrf:form.querySelector('[name=csrf]').value, url});
      const response = await fetch(form.dataset.previewApi, {method:'POST', body, signal:controller.signal, credentials:'same-origin', headers:{Accept:'application/json'}});
      const data = await response.json();
      if (!response.ok || data.error || data.url !== url) throw new Error('Preview unavailable');
      if (current !== revision) return;
      if (cache.size >= 10) cache.delete(cache.keys().next().value);
      cache.set(url, data); render(data); status.textContent = '';
    } catch (error) {
      if (current === revision && error.name !== 'AbortError') status.textContent = box.dataset.fallback;
    }
  }
  function update() {
    const url = firstLink(input.value);
    if (url === selected) return;
    selected = url; revision++; clearTimeout(timer); controller?.abort();
    result.replaceChildren(); status.textContent = ''; box.hidden = !url;
    if (!url) return;
    if (cache.has(url)) { render(cache.get(url)); return; }
    render({url, title:new URL(url).hostname}); status.textContent = box.dataset.loading;
    const current = revision;
    timer = setTimeout(() => load(url, current), 800);
  }
  input.addEventListener('input', update);
  input.addEventListener('change', update);
  window.addEventListener('pageshow', update);
  window.addEventListener('pagehide', () => { selected = ''; revision++; clearTimeout(timer); controller?.abort(); });
  update(); // Also handle restored form text. The server independently extracts the URL on publish.
});
