<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root = dirname(__DIR__) . '/src/';
$read = static fn(string $name): string => file_get_contents($root . $name);
$assert = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
foreach (['index.php', 'admin.php'] as $name) {
    $source = $read($name);
    $assert(str_contains($source, 'theme-toggle-view.php'), 'Missing shared toggle: '.$name);
    $assert((bool) preg_match('~<script src="/js/theme\.js\?v=1"></script>\s*</head>~', $source), 'Theme init must run before body');
    $assert(str_contains($source, '/css/theme.css?v=1'), 'Theme CSS missing');
}
$toggle = $read('theme-toggle-view.php');
foreach (['type="button"','role="switch"','aria-checked="false"','aria-label="Dark Mode aktivieren"','data-theme-toggle','fa-moon','hidden'] as $token) $assert(str_contains($toggle, $token), 'Missing toggle attribute: '.$token);
$js = $read('js/theme.js');
foreach (['homehub.theme','localStorage.getItem','localStorage.setItem','root.dataset.theme','aria-checked','aria-label','fa-sun','fa-moon','DOMContentLoaded',"'storage'",'catch (_)'] as $token) $assert(str_contains($js, $token), 'Missing theme behavior: '.$token);
$css = $read('css/theme.css');
foreach ([':root[data-theme="dark"]','color-scheme: dark','--theme-surface:','--theme-text:','--theme-danger-text:',':focus-visible','@media print'] as $token) $assert(str_contains($css, $token), 'Missing theme CSS: '.$token);
foreach (['app','admin','inline-editor','media-picker','admin-media','admin-users','page-actions','navigation','login-modal','admin-buttons','labels'] as $name) $assert(str_contains($read('css/'.$name.'.css'), 'var(--theme-'), 'Missing component tokens: '.$name);
$assert(!str_contains($read('css/export.css'), '--theme-') && !str_contains($read('css/chapter-document.css'), '--theme-'), 'Export must remain theme-independent');
echo "Shared theme integration and light fallbacks verified (static checks)\n";
