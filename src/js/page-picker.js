/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/* PAGE-002b: accessible page picker, without modifying existing links. */
(() => {
  const buttons = [...document.querySelectorAll('[data-page-picker]')];
  if (!buttons.length) return;
  const dialog = document.createElement('dialog');
  dialog.className = 'hh-page-picker';
  dialog.setAttribute('aria-label', 'Wiki-Seite verlinken');
  dialog.innerHTML = '<div class="hh-page-picker-head hub-dialog-header"><strong><i class="fa-solid fa-link" aria-hidden="true"></i> Wiki-Seite verlinken</strong><button type="button" data-close aria-label="Schliessen">×</button></div><label>Seite suchen<input type="search" placeholder="Titel oder Pfad" autocomplete="off"></label><div class="hh-page-picker-results" role="list"></div><p class="hh-page-picker-note">Neue Links verwenden die stabile Seiten-ID.</p>';
  document.body.append(dialog);
  const search = dialog.querySelector('input');
  const results = dialog.querySelector('.hh-page-picker-results');
  dialog.querySelector('[data-close]').addEventListener('click', () => dialog.close());
  let pages = [], selectionStart = 0, selectionEnd = 0, selectedText = '', editor = null;
  function render() {
    results.replaceChildren();
    const query = search.value.trim().toLocaleLowerCase();
    const matches = pages.filter(p => (p.title + ' ' + p.slug).toLocaleLowerCase().includes(query));
    if (!matches.length) { results.textContent = 'Keine passenden Seiten gefunden.'; return; }
    for (const page of matches) {
      const item = document.createElement('button');
      item.type = 'button'; item.className = 'hh-page-picker-item';
      const title = document.createElement('strong'); title.textContent = page.title;
      const slug = document.createElement('small'); slug.textContent = page.slug;
      item.append(title, slug);
      item.addEventListener('click', () => {
        const label = (selectedText || page.title).replace(/([\\\[\]])/g, '\\$1');
        const markdown = '[' + label + '](/page.php?id=' + encodeURIComponent(page.id) + ')';
        editor.setRangeText(markdown, selectionStart, selectionEnd, 'end');
        editor.focus(); editor.dispatchEvent(new Event('input', {bubbles:true}));
        dialog.close();
      });
      results.append(item);
    }
  }
  search.addEventListener('input', render);
  async function openPicker(target) {
    editor = target;
    selectionStart = editor.selectionStart; selectionEnd = editor.selectionEnd;
    selectedText = editor.value.slice(selectionStart, selectionEnd);
    search.value = ''; results.textContent = 'Seiten werden geladen …';
    dialog.showModal(); search.focus();
    try {
      const response = await fetch('/page-picker-api.php', {credentials:'same-origin', cache:'no-store'});
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const data = await response.json();
      if (!Array.isArray(data.pages)) throw new Error('Ungültige Antwort');
      pages = data.pages.filter(p => typeof p.id === 'string' && typeof p.title === 'string' && typeof p.slug === 'string');
      if (dialog.open) render();
    } catch (_) { if (dialog.open) results.textContent = 'Seiten konnten nicht geladen werden.'; }
  }
  for (const button of buttons) {
    button.addEventListener('click', () => {
      const targetId = button.dataset.pageTarget || 'body';
      const target = document.getElementById(targetId);
      if (!(target instanceof HTMLTextAreaElement)) return;
      openPicker(target);
    });
  }
})();
