/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  const dialog = document.getElementById('hub-login-dialog');
  const opener = document.getElementById('hub-login-open');
  if (!dialog || !opener) return;
  const close = document.getElementById('hub-login-close');
  const username = document.getElementById('hub-login-username');
  const show = () => { if (!dialog.open) dialog.showModal(); username?.focus(); };
  opener.addEventListener('click', show);
  close?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  if (dialog.hasAttribute('data-open-on-load')) show();
})();
