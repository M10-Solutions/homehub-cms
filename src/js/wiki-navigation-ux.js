/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  'use strict';
  const sidebar = document.getElementById('hub-wiki-sidebar');
  const toggle = document.getElementById('hub-nav-toggle');
  if (!sidebar || !toggle) return;
  const storage = (key, value) => { try { if (value === undefined) return localStorage.getItem(key); localStorage.setItem(key, value); } catch (_) { return null; } };
  const applyCollapsed = collapsed => { document.body.classList.toggle('hub-nav-collapsed', collapsed); toggle.setAttribute('aria-expanded', String(!collapsed)); const label = collapsed ? 'Navigation ausklappen' : 'Navigation einklappen'; toggle.title = label; requestAnimationFrame(alignToggle); toggle.setAttribute('aria-label', label); const icon = toggle.querySelector('i'); if (icon) { icon.classList.toggle('fa-chevron-right', collapsed); icon.classList.toggle('fa-chevron-left', !collapsed); } };
  const field = sidebar.querySelector('.hub-nav-search-field');
  const alignToggle = () => {
    if (!field || !field.getClientRects().length) return;
    const rect = field.getBoundingClientRect();
    toggle.style.setProperty('--hub-nav-toggle-top', `${rect.top + sidebar.scrollTop + (rect.height - 42) / 2}px`);
  };
  alignToggle();
  window.addEventListener('resize', alignToggle);
  if (field && typeof ResizeObserver !== 'undefined') new ResizeObserver(alignToggle).observe(field);
  applyCollapsed(storage('homehub.wiki.nav.collapsed') === '1');
  toggle.addEventListener('click', () => { const collapsed = !document.body.classList.contains('hub-nav-collapsed'); applyCollapsed(collapsed); storage('homehub.wiki.nav.collapsed', collapsed ? '1' : '0'); });
  const resize = document.getElementById('hub-nav-resize');
  const clamp = width => Math.max(200, Math.min(500, Math.round(width)));
  const setWidth = width => { const value = clamp(width); document.documentElement.style.setProperty('--hub-wiki-nav-width', value + 'px'); resize?.setAttribute('aria-valuenow', String(value)); storage('homehub.wiki.nav.width', String(value)); };
  const prior = Number(storage('homehub.wiki.nav.width'));
  if (prior >= 200 && prior <= 500) setWidth(prior);
  if (resize) {
    resize.addEventListener('pointerdown', event => { if (matchMedia('(max-width: 1024px)').matches) return; event.preventDefault(); resize.setPointerCapture(event.pointerId); });
    resize.addEventListener('pointermove', event => { if (resize.hasPointerCapture(event.pointerId)) setWidth(event.clientX); });
    resize.addEventListener('keydown', event => { if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); setWidth(Number(resize.getAttribute('aria-valuenow')) + (event.key === 'ArrowRight' ? 20 : -20)); } });
  }
  const tree = document.getElementById('hub-wiki-tree');
  const details = [...tree.querySelectorAll('details.hub-nav-branch')];
  const branchKey = branch => 'homehub.wiki.nav.branch.' + details.indexOf(branch);
  const activeLink = tree.querySelector('.hub-nav-title[aria-current="page"]');
  const activeBranches = new Set(activeLink ? [...activeLink.closest('.hub-nav-node').querySelectorAll(':scope > details.hub-nav-branch'), ...[...tree.querySelectorAll('details.hub-nav-branch')].filter(branch => branch.contains(activeLink))] : []);
  details.forEach(branch => {
    const key = branchKey(branch);
    const value = storage(key);
    // The current page must remain visible even if an earlier visit saved a collapsed state.
    if (activeBranches.has(branch)) branch.open = true;
    else if (value !== null) branch.open = value === '1';
    branch.addEventListener('toggle', () => { if (!search.value.trim()) storage(key, branch.open ? '1' : '0'); });
  });
  // A chapter title is also a navigation link. Keep its branch open when the page reloads.
  tree.querySelectorAll('details.hub-nav-branch > summary a.hub-nav-title').forEach(link => {
    link.addEventListener('click', event => {
      event.stopPropagation();
      const branch = link.closest('details.hub-nav-branch');
      if (!branch) return;
      for (const ancestor of [branch, ...[...tree.querySelectorAll('details.hub-nav-branch')].filter(item => item !== branch && item.contains(branch))]) {
        ancestor.open = true;
        storage(branchKey(ancestor), '1');
      }
      // Clicking the current chapter title should expand it without a pointless reload.
      if (link.getAttribute('aria-current') === 'page' && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && event.button === 0) {
        event.preventDefault();
      }
    });
  });
  const search = document.getElementById('hub-nav-search');
  const clear = document.getElementById('hub-nav-search-clear');
  const status = document.getElementById('hub-nav-search-status');
  const nodes = [...tree.querySelectorAll('.hub-nav-node')];
  const originalOpen = new Map();
  const normalize = value => value.normalize('NFKD').toLocaleLowerCase('de').trim();
  const updateSearch = () => {
    const query = normalize(search.value); clear.hidden = !query;
    if (!query) { nodes.forEach(node => { node.hidden = false; }); originalOpen.forEach((open, branch) => { branch.open = open; }); originalOpen.clear(); status.textContent = ''; return; }
    if (!originalOpen.size) details.forEach(branch => originalOpen.set(branch, branch.open));
    let hits = 0;
    nodes.slice().reverse().forEach(node => {
      const title = node.querySelector(':scope > .hub-nav-row .hub-nav-title, :scope > details > summary .hub-nav-title');
      const ownMatch = title && normalize(title.textContent).includes(query);
      const descendantMatch = [...node.querySelectorAll('.hub-nav-node')].some(child => !child.hidden);
      node.hidden = !(ownMatch || descendantMatch);
      if (ownMatch) hits++;
      const branch = node.querySelector(':scope > details'); if (branch && !node.hidden) branch.open = true;
    });
    status.textContent = hits ? hits + ' passende Einträge' : 'Keine passenden Seiten';
  };
  search.addEventListener('input', updateSearch);
  clear.addEventListener('click', () => { search.value = ''; updateSearch(); search.focus(); });
  const menu = document.getElementById('hub-nav-context-menu');
  if (!menu) return;
  let selectedSlug = '', selectedLabel = '', opener = null;
  const close = () => { menu.hidden = true; opener?.focus(); opener = null; };
  tree.querySelectorAll('[data-nav-menu-trigger]').forEach(button => {
    button.addEventListener('click', event => {
      event.preventDefault(); event.stopPropagation();
      if (!menu.hidden && opener === button) { close(); return; }
      selectedSlug = button.dataset.slug || ''; selectedLabel = button.dataset.label || ''; opener = button;
      menu.querySelectorAll('[data-nav-action]:not([data-nav-action="create"])').forEach(item => item.hidden = !selectedSlug);
      menu.hidden = false;
      const rect = button.getBoundingClientRect();
      menu.style.left = Math.max(8, Math.min(rect.right, innerWidth - menu.offsetWidth - 8)) + 'px';
      menu.style.top = Math.max(8, Math.min(rect.top, innerHeight - menu.offsetHeight - 8)) + 'px';
      menu.querySelector('button:not([hidden])')?.focus();
    });
  });
  document.addEventListener('click', event => { if (!menu.hidden && !menu.contains(event.target) && !event.target.closest('[data-nav-menu-trigger]')) close(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && !menu.hidden) { event.preventDefault(); close(); } });
  menu.addEventListener('click', event => {
    const action = event.target.closest('[data-nav-action]')?.dataset.navAction;
    if (!action) return;
    const slug = selectedSlug; close();
    if (action === 'create') {
      const dialog = document.getElementById('hub-page-create');
      if (dialog) { const field = dialog.querySelector('[name="slug"]'); if (field) field.value = slug ? slug + '/' : ''; dialog.showModal(); field?.dispatchEvent(new Event('input')); field?.focus(); }
      return;
    }
    // Settings/move/delete dialogs contain page-specific revision and form data.
    // Navigate to the selected page instead of modifying a different page's form.
    const current = document.getElementById('inline-slug')?.value;
    if (slug === current) document.querySelector('[data-open-page-dialog="hub-page-' + action + '"]')?.click();
    else if (slug) { try { sessionStorage.setItem('homehub.nav.pendingAction', JSON.stringify({ slug, action })); } catch (_) {} window.location.assign((tree.querySelector('[data-nav-slug="' + CSS.escape(slug) + '"] a.hub-nav-title')?.getAttribute('href') || '/' + slug.split('/').map(encodeURIComponent).join('/')).replace(/&amp;/g, '&')); }
  });
  try { const pending = JSON.parse(sessionStorage.getItem('homehub.nav.pendingAction') || 'null'); sessionStorage.removeItem('homehub.nav.pendingAction'); const current = document.getElementById('inline-slug')?.value; if (pending && pending.slug === current && ['settings', 'move', 'delete'].includes(pending.action)) { document.querySelector('[data-open-page-dialog="hub-page-' + pending.action + '"]')?.click(); } } catch (_) {}
})();
