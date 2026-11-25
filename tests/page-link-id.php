<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
// Smoke test for stable page-link lookup. Run inside the container.
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use HomeHub\PageIdentity;
use HomeHub\PageRepository;
$dir = sys_get_temp_dir() . '/homehub-page-link-' . bin2hex(random_bytes(6));
mkdir($dir, 0700, true);
$id = PageIdentity::generate();
try {
    mkdir($dir . '/a');
    file_put_contents($dir . '/a/one.md', "---\nid: $id\ntitle: Test\n---\nHello");
    $repo = new PageRepository($dir);
    if ($repo->findById($id)?->slug !== 'a/one') throw new RuntimeException('ID lookup failed');
    mkdir($dir . '/b');
    rename($dir . '/a/one.md', $dir . '/b/two.md');
    if ($repo->findById($id)?->slug !== 'b/two') throw new RuntimeException('Move lookup failed');
    if ($repo->findById('p_invalid') !== null) throw new RuntimeException('Invalid ID accepted');
    echo "PAGE-002 OK\n";
} finally {
    foreach (glob($dir . '/{a,b}/*.md', GLOB_BRACE) ?: [] as $f) unlink($f);
    rmdir($dir . '/a'); rmdir($dir . '/b'); rmdir($dir);
}
