/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  function enhance() {
    document.querySelectorAll('.markdown-content pre > code, .preview-code pre > code').forEach(code => {
      const pre = code.parentElement;
      if (pre.dataset.enhanced) return;
      pre.dataset.enhanced = '1';
      const match = code.className.match(/(?:^|\s)(?:language|lang)-([a-z0-9_+-]+)/i);
      const lang = match ? match[1].toLowerCase() : '';
      if (lang && window.Prism && Prism.languages[lang]) Prism.highlightElement(code);
      pre.classList.add('hub-code-block');
      const bar = document.createElement('div');
      bar.className = 'hub-code-toolbar';
      const title = document.createElement('span');
      title.textContent = lang ? lang.toUpperCase() : 'CODE';
      const copy = document.createElement('button');
      copy.type = 'button'; copy.className = 'hub-code-copy';
      copy.setAttribute('aria-label', 'Code kopieren');
      copy.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i><span>Kopieren</span>';
      copy.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(code.textContent); copy.querySelector('span').textContent = 'Kopiert'; }
        catch { copy.querySelector('span').textContent = 'Nicht möglich'; }
        setTimeout(() => { copy.querySelector('span').textContent = 'Kopieren'; }, 1600);
      });
      bar.append(title, copy);
      pre.parentNode.insertBefore(bar, pre);
      pre.parentNode.classList.add('hub-code-wrap');
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', enhance);
  else enhance();
})();
