/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/* UAT-002a: shared, accessible feedback without external dependencies. */
(() => {
  'use strict';
  const ready = () => {
    const stack = document.createElement('div');
    stack.className = 'hub-toast-stack';
    stack.setAttribute('aria-label', 'Mitteilungen');
    document.body.append(stack);
    const dialog = document.createElement('dialog');
    dialog.className = 'hub-confirm-dialog';
    dialog.innerHTML = '<h2 class="hub-dialog-header" id="hub-confirm-title">Bestätigung</h2><p id="hub-confirm-message"></p><div class="hub-confirm-actions"><button type="button" class="admin-btn" data-hub-no>Abbrechen</button><button type="button" class="admin-btn danger" data-hub-yes>Bestätigen</button></div>';
    dialog.setAttribute('aria-labelledby', 'hub-confirm-title');
    dialog.setAttribute('aria-describedby', 'hub-confirm-message');
    document.body.append(dialog);
    let pending = null;
    const finish = result => { if (!pending) return; const resolve = pending; pending = null; if (dialog.open) dialog.close(); resolve(result); };
    dialog.querySelector('[data-hub-no]').addEventListener('click', () => finish(false));
    dialog.querySelector('[data-hub-yes]').addEventListener('click', () => finish(true));
    dialog.addEventListener('cancel', e => { e.preventDefault(); finish(false); });
    dialog.addEventListener('click', e => { if (e.target === dialog) finish(false); });
    const notify = (message, type = 'success', duration) => {
      if (!message) return;
      const kind = ['success', 'info', 'error', 'delete'].includes(type) ? type : 'info';
      const item = document.createElement('div');
      item.className = `hub-toast hub-toast-${kind}`;
      item.setAttribute('role', kind === 'error' ? 'alert' : 'status');
      const text = document.createElement('span'); text.textContent = String(message);
      const close = document.createElement('button'); close.type = 'button'; close.className = 'hub-toast-close';
      close.setAttribute('aria-label', 'Mitteilung schliessen'); close.textContent = '×';
      close.addEventListener('click', () => item.remove());
      item.append(text, close); stack.append(item);
      const ms = duration === undefined ? (kind === 'error' ? 0 : 5000) : duration;
      if (ms > 0) window.setTimeout(() => item.remove(), ms);
      return item;
    };
    const confirm = (message, options = {}) => new Promise(resolve => {
      if (pending) { resolve(false); return; }
      pending = resolve;
      dialog.querySelector('#hub-confirm-title').textContent = options.title || 'Bitte bestätigen';
      dialog.querySelector('#hub-confirm-message').textContent = String(message);
      const yes = dialog.querySelector('[data-hub-yes]');
      yes.textContent = options.confirmLabel || 'Bestätigen';
      yes.classList.toggle('danger', options.danger !== false);
      yes.classList.toggle('primary', options.danger === false);
      dialog.showModal();
      dialog.querySelector('[data-hub-no]').focus();
    });
    window.HomeHubFeedback = { notify, confirm };
    // Server-rendered notices: keep errors visible, dismiss successful actions after 5 s.
    document.querySelectorAll('[data-hub-notice]').forEach(el => {
      const message = el.textContent.trim();
      if (message) notify(message, el.dataset.hubNotice);
      el.remove();
    });
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, { once: true });
  else ready();
})();
