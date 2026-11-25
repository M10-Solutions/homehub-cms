<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/** UI-012: compact navigation and theme button. */
declare(strict_types=1);
$src = dirname(__DIR__) . '/src/';
$read = static fn (string $file): string => file_get_contents($src . $file);
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$assert(!str_contains($read('index.php'), '<div class="nav-label">INTRANET</div>'), 'Wiki intranet heading still visible');
$assert(!str_contains($read('admin.php'), '<strong>Navigation</strong>'), 'Admin duplicate heading still visible');
$assert(str_contains($read('admin.php'), '<h2>Verwaltung</h2>'), 'Admin section heading missing');
$assert(str_contains($read('css/navigation.css'), 'font-size:14px;'), 'Wiki navigation 14px missing');
$assert(str_contains($read('css/admin-unified.css'), 'font-size:14px;text-decoration:none}'), 'Admin links 14px missing');
$toggle = $read('theme-toggle-view.php');
$assert(!str_contains($toggle, 'hub-theme-track'), 'Old theme slider still present');
$assert(str_contains($toggle, 'fa-moon'), 'Light mode icon missing');
$js = $read('js/theme.js');
foreach (['fa-sun', 'fa-moon', "theme === 'dark'", 'aria-label', 'localStorage.setItem'] as $token) $assert(str_contains($js, $token), 'Theme behavior missing: '.$token);
echo "OK: UI-012 navigation and theme icon checks\n";
