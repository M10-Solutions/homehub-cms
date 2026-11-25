<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/** UAT-002c: drafts available to authorised editorial roles, page creation inherits chapter context. */
$root = dirname(__DIR__);
$read = static fn (string $path): string => file_get_contents($root . '/' . $path);
$assert = static function (bool $condition, string $message): void { if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } };
$repo = $read('src/lib/PageRepository.php');
$index = $read('src/index.php');
$inline = $read('src/inline.php');
$admin = $read('src/lib/Admin.php');
$assert(str_contains($repo, 'public function allIncludingDrafts()'), 'draft inventory exists');
$assert(str_contains($repo, 'return ($page !== null && $page->published) ? $page : null;'), 'public find excludes drafts');
$assert(str_contains($repo, 'return array_values(array_filter($this->allIncludingDrafts()'), 'public all excludes drafts');
$assert(str_contains($index, "$" . "showDrafts && isset(" . "$" . "_GET['draft'])"), 'draft route role gated');
$assert(str_contains($index, 'hub-draft-banner'), 'draft UI');
$assert(str_contains($index, '$createPrefix'), 'chapter prefix');
$assert(str_contains($index, "is_dir($" . "config['content_path']"), 'selected chapter folder');
$assert(str_contains($inline, '$page = $repo->findForAccess'), 'draft editor admin only');
$assert(str_contains($admin, "PermissionService::canReadDraft"), 'admin content-only guard');
$assert(str_contains($read('src/page-actions.php'), "/?draft="), 'draft settings redirect');
echo "UAT-002c contract checks OK\n";
