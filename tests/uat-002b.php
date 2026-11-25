<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$src = dirname(__DIR__) . '/src/';
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
foreach (['index.php' => 'hub-wiki-sidebar', 'admin.php' => 'admin-sidebar'] as $file => $id) {
    $html = file_get_contents($src . $file);
    foreach (['responsive-navigation.css?v=1', 'responsive-navigation.js?v=1', 'hub-mobile-nav-backdrop', 'aria-controls="'.$id.'" aria-expanded="false"'] as $token) $assert(str_contains($html, $token), "$file missing $token");
}
$css = file_get_contents($src.'css/responsive-navigation.css');
$js = file_get_contents($src.'js/responsive-navigation.js');
foreach (['max-width: 1024px', 'hub-mobile-nav-open', 'margin-left: 0', 'hub-nav-edge-toggle', 'hub-nav-resize', '--theme-surface'] as $token) $assert(str_contains($css, $token), "CSS missing $token");
foreach (['max-width: 1024px', 'aria-expanded', 'Escape', 'main.inert = open', 'sidebar.inert', 'pageshow', "addEventListener('change'", 'queueMicrotask'] as $token) $assert(str_contains($js, $token), "JS missing $token");
$assert(!str_contains($js, 'localStorage'), 'Mobile state must not overwrite desktop settings');
echo "OK: UAT-002b responsive navigation structure\n";
