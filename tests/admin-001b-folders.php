<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

require dirname(__DIR__) . '/src/lib/PageFolders.php';
require dirname(__DIR__) . '/src/lib/Admin.php';
use HomeHub\PageFolders;
$root = sys_get_temp_dir() . '/homehub-folders-' . bin2hex(random_bytes(6));
mkdir($root . '/smarthome/raum', 0770, true);
mkdir($root . '/ubuntu', 0770, true);
file_put_contents($root . '/smarthome/page.md', '# Test');
try {
    $folders = PageFolders::all($root);
    foreach (['', 'smarthome', 'smarthome/raum', 'ubuntu'] as $expected) {
        if (!in_array($expected, $folders, true)) throw new RuntimeException('Missing folder: ' . $expected);
    }
    if (in_array('smarthome/page.md', $folders, true)) throw new RuntimeException('File returned as folder');
    if (function_exists('symlink') && @symlink($root . '/smarthome', $root . '/linked')) {
        if (in_array('linked', PageFolders::all($root), true)) throw new RuntimeException('Symlink returned');
        unlink($root . '/linked');
    }
    echo "ADMIN-001b folder discovery OK\n";
} finally {
    @unlink($root . '/smarthome/page.md');
    @rmdir($root . '/smarthome/raum'); @rmdir($root . '/smarthome'); @rmdir($root . '/ubuntu'); @rmdir($root);
}
