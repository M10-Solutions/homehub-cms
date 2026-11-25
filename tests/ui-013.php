<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
if (is_file(dirname(__DIR__)."/vendor/autoload.php")) require_once dirname(__DIR__)."/vendor/autoload.php";
require_once dirname(__DIR__)."/src/lib/AlignedMarkdown.php";
require_once dirname(__DIR__).'/src/lib/Admin.php';
if (class_exists(League\CommonMark\Environment\Environment::class)) {
$dir = sys_get_temp_dir().'/hh-ui013-'.bin2hex(random_bytes(8));
mkdir($dir);
try {
    $admin = new HomeHub\Admin($dir, $dir);
    $admin->assertCreateAvailable('example');
    file_put_contents($dir.'/example.md', "---\npublished: false\n---\nDraft");
    try { $admin->assertCreateAvailable('example'); throw new LogicException('Duplicate accepted'); }
    catch (RuntimeException $e) { if ($e->getMessage() !== 'Diese Seite existiert bereits.') throw $e; }
    try { $admin->assertCreateAvailable('../example'); throw new LogicException('Invalid path accepted'); }
    catch (InvalidArgumentException $e) {}
} finally {
    if (is_file($dir.'/example.md')) unlink($dir.'/example.md');
    rmdir($dir);
}
} else { echo "Local path check omitted: CommonMark unavailable\n"; }
$src = dirname(__DIR__).'/src/';
foreach (['index.php','admin.php','admin-users-view.php','admin-tags-view.php','media-picker-view.php'] as $file) {
    preg_match_all('~<dialog\b.*?</dialog>~s', file_get_contents($src.$file), $matches);
    foreach ($matches[0] as $dialog) {
        if (!preg_match('~hub-dialog-header.*?<i class="fa-~s', $dialog)) throw new RuntimeException('Missing icon: '.$file);
    }
}
foreach (['js/hub-feedback.js','js/page-picker.js'] as $file) {
    if (!preg_match('~hub-dialog-header.*?<i class="fa-~s', file_get_contents($src.$file))) throw new RuntimeException('Missing dynamic icon: '.$file);
}
echo "UI-013 path availability and dialog icon checks passed\n";
