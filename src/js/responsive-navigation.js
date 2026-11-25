/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  'use strict';
  const toggle = document.querySelector('.hub-mobile-nav-toggle');
  const sidebar = toggle && document.getElementById(toggle.getAttribute('aria-controls'));
  const backdrop = document.querySelector('.hub-mobile-nav-backdrop');
  const main = document.querySelector('.main-area, .admin-main');
  if (!sidebar || !backdrop || !main) return;
  const mobile = matchMedia('(max-width: 1024px)');
  const body = document.body;
  const menu = document.getElementById('hub-nav-context-menu');
  let open = false;
  const setOpen = (value, restoreFocus = false) => {
    open = mobile.matches && value;
    body.classList.toggle('hub-mobile-nav-open', open);
    backdrop.hidden = !open;
    sidebar.inert = mobile.matches && !open;
    main.inert = open;
    toggle.setAttribute('aria-expanded', String(open));
    const label = open ? 'Navigation schliessen' : 'Navigation öffnen';
    toggle.setAttribute('aria-label', label);
    toggle.title = label;
    const icon = toggle.querySelector('i');
    icon.classList.toggle('fa-bars', !open);
    icon.classList.toggle('fa-xmark', open);
    if (!open && menu) menu.hidden = true;
    if (open) sidebar.querySelector('input, a, summary, button')?.focus({preventScroll: true});
    else if (restoreFocus && mobile.matches) toggle.focus({preventScroll: true});
  };
  const sync = () => {
    const hadFocus = sidebar.contains(document.activeElement);
    toggle.hidden = !mobile.matches;
    setOpen(false, hadFocus);
  };
  body.classList.add('hub-responsive-nav');
  sync();
  mobile.addEventListener('change', sync);
  toggle.addEventListener('click', () => setOpen(!open, open));
  toggle.closest('header').addEventListener('click', event => {
    if (open && !toggle.contains(event.target) && event.target.closest('button, a')) setOpen(false);
  });
  backdrop.addEventListener('click', () => setOpen(false, true));
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape' || !open || event.defaultPrevented || (menu && !menu.hidden) || document.querySelector('dialog[open]')) return;
    event.preventDefault();
    setOpen(false, true);
  });
  // Capture also sees chapter links whose existing handler stops propagation.
  sidebar.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!open || !link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank' || link.hasAttribute('download')) return;
    // Allow the tree's existing chapter handler to finish first.
    queueMicrotask(() => setOpen(false, true));
  }, true);
  // Context actions may open a modal; never leave the content inert behind it.
  menu?.addEventListener('click', event => {
    if (open && event.target.closest('[data-nav-action]')) queueMicrotask(() => setOpen(false));
  });
  window.addEventListener('pageshow', () => setOpen(false));
})();
