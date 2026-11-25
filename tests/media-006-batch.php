<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
foreach (['MediaLibrary','MediaRegistry','MediaOperationLock','MediaMover','MediaDeleter','MediaBatch'] as $class) require __DIR__ . '/../src/lib/' . $class . '.php';
$root = sys_get_temp_dir() . '/homehub-media006-' . bin2hex(random_bytes(6));
mkdir($root . '/upload/sub', 0777, true); mkdir($root . '/config');
try {
    foreach (['a.txt','b.txt','sub/b.txt','sub/c.txt'] as $file) file_put_contents($root . '/upload/' . $file, 'plain text fixture');
    $media = new HomeHub\MediaLibrary($root . '/upload');
    $reg = new HomeHub\MediaRegistry($root . '/config');
    $id = $reg->register('a.txt'); $reg->register('b.txt'); $reg->register('sub/b.txt');
    $batch = new HomeHub\MediaBatch($media, $reg, new HomeHub\MediaOperationLock($root . '/config'));
    [$done, $errors] = $batch->run('move', '', 'sub', ['a.txt','b.txt','sub/c.txt','a.txt']);
    if ($done !== 1 || count($errors) !== 2 || $reg->pathForId($id) !== 'sub/a.txt' || !is_file($root . '/upload/b.txt')) throw new RuntimeException('Move, collision or scope failed');
    [$done, $errors] = $batch->run('delete', 'sub', '', ['sub/a.txt','sub/b.txt']);
    if ($done !== 2 || $errors || $reg->idForPath('sub/a.txt') !== null || is_file($root . '/upload/sub/b.txt') || !is_file($root . '/upload/b.txt')) throw new RuntimeException('Delete failed');
    echo "MEDIA-006 batch checks passed\n";
} finally {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); rmdir($root);
}
