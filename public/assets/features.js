/* Friends-SNCMS · Andreas Holzer @WebCrew (GitHub) · Copyright © 2026 Andreas Holzer. */
(() => {
  document.querySelectorAll('.voice-setup-form').forEach(form => {
    const select = form.querySelector('[name=voice_setup]'), fields = form.querySelector('.turn-fields'), advanced = form.querySelector('.voice-advanced');
    const update = () => { fields.hidden = select.value !== 'turn'; advanced.hidden = select.value !== 'advanced'; if (select.value === 'advanced') advanced.open = true; };
    select.addEventListener('change', update); update();
  });
  document.querySelectorAll('.feed-composer').forEach(form => {
    const menus = [...form.querySelectorAll('details')];
    menus.forEach(menu => menu.addEventListener('toggle', () => { if (menu.open) menus.filter(other => other !== menu).forEach(other => { other.open = false; }); }));
    form.addEventListener('keydown', event => { if (event.key === 'Escape') menus.forEach(menu => { if (menu.open) { menu.open = false; menu.querySelector('summary').focus(); } }); });
    document.addEventListener('click', event => { if (!form.contains(event.target)) menus.forEach(menu => { menu.open = false; }); });
    const textarea = form.querySelector('textarea[name=body]');
    form.querySelectorAll('.feed-emoji').forEach(button => button.addEventListener('click', () => {
      const start = textarea.selectionStart, end = textarea.selectionEnd;
      if (textarea.value.length - (end - start) + button.textContent.length > textarea.maxLength) return;
      textarea.setRangeText(button.textContent, start, end, 'end');
      textarea.focus(); textarea.dispatchEvent(new Event('input', {bubbles:true}));
      button.closest('details').open = false;
    }));
    const fileInput = form.querySelector('input[type=file]');
    const filesPreview = form.querySelector('.attachment-previews');
    let objectUrls = [];
    const clear = () => { objectUrls.forEach(url => URL.revokeObjectURL(url)); objectUrls = []; filesPreview?.replaceChildren(); };
    fileInput?.addEventListener('change', () => {
      clear();
      for (const file of fileInput.files) {
        const item = document.createElement('span');
        item.className = 'attachment-chip';
        if (['image/jpeg','image/png','image/webp'].includes(file.type) && file.size <= 10 * 1048576) {
          const image = document.createElement('img');
          image.src = URL.createObjectURL(file); objectUrls.push(image.src);
          image.alt = ''; item.append(image);
        }
        const label = document.createElement('span'); label.textContent = file.name; item.append(label);
        filesPreview.append(item);
      }
    });
    addEventListener('pagehide', clear, {once:true});
    form.addEventListener('submit', event => {
      if (!textarea.value.trim() && !fileInput?.files.length) {
        event.preventDefault(); textarea.required = true; textarea.reportValidity(); textarea.required = false;
      }
      if (fileInput?.files.length > Number(form.dataset.maxAttachments)) { event.preventDefault(); fileInput.setCustomValidity(form.dataset.attachmentError); fileInput.reportValidity(); }
    });
    fileInput?.addEventListener('change', () => fileInput.setCustomValidity(''));
  });
})();
