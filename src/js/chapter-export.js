/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.getElementById('hub-chapter-export-dialog');
  const trigger = document.getElementById('hub-chapter-export-open');
  if (!dialog || !trigger) return;
  const form = dialog.querySelector('form');
  const options = form.querySelector('#hub-export-document-options');
  const syncOptions = () => {
    const visible = form.elements.scope.value === 'tree' && form.elements.format.value !== 'zip';
    options.hidden = !visible;
    options.disabled = !visible;
  };
  form.addEventListener('change', syncOptions);
  trigger.addEventListener('click', () => {
    form.reset();
    syncOptions();
    dialog.showModal();
  });
  syncOptions();
  dialog.querySelector('[data-chapter-export-close]')?.addEventListener('click', () => dialog.close());
  dialog.querySelector('form').addEventListener('submit', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
});
