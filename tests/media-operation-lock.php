<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'HomeHub\\')) {
        $file = dirname(__DIR__) . '/src/lib/' . substr($class, 8) . '.php';
        if (is_file($file)) require $file;
    }
});
use HomeHub\{MediaLibrary,MediaRegistry,MediaMover,MediaDeleter,MediaOperationLock};
$root = sys_get_temp_dir() . '/homehub-media-lock-' . bin2hex(random_bytes(6));
foreach (['config','upload/a','upload/b','content'] as $dir) mkdir($root . '/' . $dir, 0770, true);
try {
    file_put_contents($root . '/upload/a/example.txt', 'example');
    $media = new MediaLibrary($root . '/upload');
    $registry = new MediaRegistry($root . '/config');
    $lock = new MediaOperationLock($root . '/config');
    $id = $registry->register('a/example.txt');
    $mover = new MediaMover($media, $registry, $lock);
    $mover->move('a/example.txt', 'b', 'renamed.txt');
    if ($registry->pathForId($id) !== 'b/renamed.txt') throw new RuntimeException('ID lost after move');
    file_put_contents($root . '/content/page.md', '![example](/media.php?id=' . $id . ')');
    $deleter = new MediaDeleter($media, $registry, $lock);
    // Explicitly confirmed deletion is permitted even if a page still references the ID.
    $deleter->delete('b/renamed.txt');
    if (is_file($root . '/upload/b/renamed.txt') || $registry->idForPath('b/renamed.txt') !== null) throw new RuntimeException('Delete failed');
    echo "OK: move, stable ID, deliberate deletion\n";
} finally {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    rmdir($root);
}
