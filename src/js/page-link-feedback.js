/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
(() => {
  let pending = false;
  document.addEventListener('click', async event => {
    const link = event.target.closest?.('#inline-read-content a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.download || (link.target && link.target !== '_self')) return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || url.pathname !== '/page.php' || !url.searchParams.has('id')) return;
    if (!window.HomeHubFeedback) return;
    event.preventDefault();
    if (pending) return;
    pending = true;
    try {
      const response = await fetch(url.href, {method:'HEAD', credentials:'same-origin', cache:'no-store'});
      if (response.status === 401 || response.status === 403) {
        window.HomeHubFeedback.notify('Du hast keine Berechtigung, diese Seite zu öffnen.', 'error');
      } else { location.assign(url.href); }
    } catch { window.HomeHubFeedback.notify('Die Seite konnte nicht geprüft werden. Bitte erneut versuchen.', 'error'); }
    finally { pending = false; }
  });
})();
