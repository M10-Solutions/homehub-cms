/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.getElementById('hub-chapter-export-dialog');
  const trigger = document.getElementById('hub-chapter-export-open');
  if (!dialog || !trigger) return;
  trigger.addEventListener('click', () => dialog.showModal());
  dialog.querySelector('[data-chapter-export-close]')?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
});
