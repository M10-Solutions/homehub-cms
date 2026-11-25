<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/** UAT-002a follow-up: destructive action feedback and styling. */
$root = dirname(__DIR__);
$read = static fn(string $path): string => file_get_contents($root . '/' . $path);
$assert = static function (bool $ok, string $msg): void { if (!$ok) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } };
$admin = $read('src/admin.php');
$assert(str_contains($admin, "' Mediendatei gelöscht.'"), 'singular media deletion');
$assert(str_contains($admin, "' Mediendateien gelöscht.'"), 'plural media deletion');
$assert(str_contains($admin, "'Schlagwort gelöscht.'"), 'tag deletion feedback');
$assert(str_contains($admin, 'data-hub-notice="<?= h($noticeType) ?>"'), 'notification severity passed to UI');
$assert(str_contains($read('src/css/hub-feedback.css'), '.hub-toast-delete{border-left-color:#9c2434}'), 'dark red delete stripe');
$assert(str_contains($read('src/css/page-actions.css'), 'button[type=submit].hub-danger'), 'delete button specificity');
$assert(str_contains($read('src/page-actions.php'), "hub_page_deleted_notice"), 'page deletion flash');
$assert(str_contains($read('src/index.php'), 'data-hub-notice="delete"'), 'page deletion notification');
$assert(!str_contains($read('src/js/page-actions.js'), "HomeHubFeedback.confirm('Diese Wiki-Seite"), 'no duplicate delete confirmation');
echo "UAT-002a follow-up OK\n";
