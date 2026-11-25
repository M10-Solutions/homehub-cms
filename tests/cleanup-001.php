<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
require dirname(__DIR__) . '/src/lib/JSONAuthenticator.php';
use HomeHub\JSONAuthenticator;
$dir = sys_get_temp_dir() . '/hh-cleanup-' . bin2hex(random_bytes(8));
mkdir($dir, 0700);
function check(bool $condition): void { if (!$condition) throw new RuntimeException('CLEANUP assertion failed'); }
try {
    $auth = new JSONAuthenticator($dir);
    check($auth->allUsers() === []);
    check($auth->registerUser('admin', 'test-password-123', 'admin', initialOnly: true));
    $raw = file_get_contents($dir . '/users.json');
    $blocked = false;
    try { $auth->registerUser('admin2', 'test-password-123', 'admin', initialOnly: true); }
    catch (RuntimeException) { $blocked = true; }
    check($blocked && file_get_contents($dir . '/users.json') === $raw);
    $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    check(password_verify('test-password-123', $data[0]['password']));
    check($auth->registerUser('editor', 'test-password-123', 'editor'));
    check(count($auth->allUsers()) === 2);
    $root = dirname(__DIR__);
    require $root . '/src/lib/Admin.php';
    check(!method_exists(HomeHub\Admin::class, 'listPages') && !method_exists(HomeHub\Admin::class, 'passwordHash'));
    check(str_contains(file_get_contents($root . '/tests/register-page-ids.php'), '/bin/register-page-ids.php'));
    if (is_file($root . '/vendor/autoload.php')) {
        require $root . '/vendor/autoload.php';
        $md = HomeHub\AlignedMarkdown::converter();
        $html = (string)$md->convert("<script>alert(1)</script>\n\n[a](javascript:alert)\n\n| A | B |\n|---|---|\n| x | y |");
        check(!str_contains($html, '<script>') && !str_contains($html, 'href="javascript:') && str_contains($html, '<table>'));
    } else echo "CommonMark integration unavailable\n";
} finally { foreach (glob($dir . '/*') as $file) unlink($file); rmdir($dir); }
