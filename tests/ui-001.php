<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root = dirname(__DIR__).'/src/';
$css = file_get_contents($root.'css/admin-buttons.css');
$font = file_get_contents($root.'vendor/fontawesome/css/all.min.css');
$count = 0;
foreach (['admin.php','admin-users-view.php'] as $file) {
    $source = file_get_contents($root.$file);
    preg_match_all('~<button\b(.*?)</button>~s', $source, $matches);
    foreach ($matches[0] as $button) {
        // The shared mobile header toggle is an icon-only navigation control, not an admin action.
        if (str_contains($button, 'class="hub-mobile-nav-toggle"')) {
            if (!str_contains($button, 'aria-label=') || !str_contains($button, 'aria-controls="admin-sidebar"') || !str_contains($button, 'fa-bars') || !str_contains($font, '.fa-bars')) throw new RuntimeException('Mobile navigation control incomplete');
            continue;
        }
        if (str_contains($button, 'class="hub-auth-button"')) {
            if (!str_contains($button, 'aria-label="Abmelden"') || !str_contains($button, 'fa-right-from-bracket') || str_contains($button, '<span>')) throw new RuntimeException('Logout icon-only contract failed');
            continue;
        }
        ++$count;
        if (!str_contains($button,'admin-btn') || !preg_match('/fa-solid (fa-[a-z-]+)/',$button,$icon) || !str_contains($font,'.'.$icon[1]) || !str_contains($button,'<span>')) throw new RuntimeException('Missing button style, icon or label: '.$button);
        if (str_contains($button,'<span>Löschen</span>') && !str_contains($button,'danger')) throw new RuntimeException('Delete styling missing');
    }
}
if ($count < 23 || !str_contains($css,'--admin-action-danger:#9c2434') || !str_contains($css,':focus-visible') || !str_contains($css,':disabled')) throw new RuntimeException('Shared styles incomplete');
if (!str_contains(file_get_contents($root.'admin.php'),'/css/admin-buttons.css') || !str_contains(file_get_contents($root.'js/admin-media.js'),"b.querySelector('span').textContent")) throw new RuntimeException('Integration missing');
echo "UI-001: $count button templates checked\n";
