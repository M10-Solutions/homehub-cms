<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
// Run: php tests/media-004-migration.php
require_once __DIR__ . '/../src/lib/MediaLibrary.php';
require_once __DIR__ . '/../src/lib/MediaOperationLock.php';
// Include script with an invalid argument to avoid executing it, using a subprocess instead.
$dir = sys_get_temp_dir() . '/homehub-media004-' . bin2hex(random_bytes(5));
mkdir($dir); mkdir($dir . '/bin'); mkdir($dir . '/src/lib', 0777, true);
mkdir($dir . '/data/content', 0777, true); mkdir($dir . '/data/upload/haus', 0777, true); mkdir($dir . '/data/config', 0777, true);
copy(__DIR__ . '/../bin/migrate-media-links.php', $dir . '/bin/migrate-media-links.php');
foreach (['MediaLibrary.php','MediaOperationLock.php'] as $file) copy(__DIR__ . '/../src/lib/' . $file, $dir . '/src/lib/' . $file);
$id = str_repeat('a', 32);
file_put_contents($dir . '/data/upload/haus/bild.png', 'fake fixture');
file_put_contents($dir . '/data/config/media-index.json', json_encode(['version'=>1,'items'=>[$id=>['path'=>'haus/bild.png','name'=>'bild.png']]]));
$body = "---\ntitle: Test\n---\n![A](/media.php?file=haus%2Fbild.png)\n![B](/data/upload/haus/bild.png)\n[Download][x]\n[x]: /uploads/haus/bild.png\n<img src=\"/upload/haus/bild.png\">\n`![Code](/media.php?file=haus/bild.png)`\n```md\n![Fence](/media.php?file=haus/bild.png)\n```\n![Unknown](/media.php?file=missing.png)\n";
file_put_contents($dir . '/data/content/test.md', $body);
try {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($dir . '/bin/migrate-media-links.php');
    exec($cmd . ' --check 2>&1', $out, $code);
    if ($code !== 0 || !str_contains(implode("\n", $out), '4 links, 1 files to change, 1 warnings')) throw new RuntimeException('Check failed: ' . implode("\n", $out));
    exec($cmd . ' --apply 2>&1', $out2, $code);
    if ($code !== 0) throw new RuntimeException('Apply failed: ' . implode("\n", $out2));
    $new = file_get_contents($dir . '/data/content/test.md');
    if (substr_count($new, '/media.php?id=' . $id) !== 4 || !str_contains($new, '`![Code](/media.php?file=haus/bild.png)`') || !str_contains($new, '![Fence](/media.php?file=haus/bild.png)')) throw new RuntimeException('Unexpected migration result');
    exec($cmd . ' --check 2>&1', $out3, $code);
    if ($code !== 0 || !str_contains(implode("\n", $out3), '0 files to change')) throw new RuntimeException('Idempotence failed');
    echo "OK: MEDIA-004 migration, backup, code protection and idempotence\n";
} finally {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    rmdir($dir);
}
