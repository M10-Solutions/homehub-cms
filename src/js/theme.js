/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/* UI-011: resolve the preference before first paint; no server-side user data. */
(() => {
    'use strict';
    const key = 'homehub.theme';
    const root = document.documentElement;
    let theme = 'light';
    try { if (localStorage.getItem(key) === 'dark') theme = 'dark'; } catch (_) { /* Storage unavailable. */ }
    const apply = (value) => {
        theme = value === 'dark' ? 'dark' : 'light';
        root.dataset.theme = theme;
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-checked', String(theme === 'dark'));
            const label = theme === 'dark' ? 'Light Mode aktivieren' : 'Dark Mode aktivieren';
            button.title = label;
            button.setAttribute('aria-label', label);
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-sun', theme === 'dark');
                icon.classList.toggle('fa-moon', theme !== 'dark');
            }
        });
    };
    apply(theme);
    document.addEventListener('DOMContentLoaded', () => {
        apply(theme);
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.hidden = false;
            button.addEventListener('click', () => {
                apply(theme === 'dark' ? 'light' : 'dark');
                try { localStorage.setItem(key, theme); } catch (_) { /* Still switch for this page. */ }
            });
        });
    });
    window.addEventListener('storage', (event) => {
        if (event.key === key || event.key === null) apply(event.newValue);
    });
})();
