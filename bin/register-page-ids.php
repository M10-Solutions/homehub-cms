<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

// Run from project root: php bin/register-page-ids.php [--apply]
// Default is a dry run. Back up data/content before --apply.
$root = dirname(__DIR__);
$autoload = $root . '/src/vendor/autoload.php';
if (!is_file($autoload)) $autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Composer dependencies are required (run inside the HomeHub PHP container).\n");
    exit(2);
}
require $autoload;
require_once $root . '/src/lib/PageIdentity.php';
require_once $root . '/src/lib/MediaOperationLock.php';

use HomeHub\PageIdentity;
use HomeHub\MediaOperationLock;

if (array_diff(array_slice($argv, 1), ['--apply'])) {
    fwrite(STDERR, "Usage: php bin/register-page-ids.php [--apply]\n");
    exit(2);
}
$apply = in_array('--apply', $argv, true);
$content = $root . '/data/content';
$data = $root . '/data';
try {
    (new MediaOperationLock($data))->run(static function () use ($content, $apply): void {
        $ids = PageIdentity::inventory($content); // fail on duplicate or invalid IDs
        $root = realpath($content);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        $missing = [];
        foreach ($iterator as $entry) {
            if (!$entry->isFile() || $entry->isLink() || strtolower($entry->getExtension()) !== 'md') continue;
            $path = realpath($entry->getPathname());
            if ($path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) continue;
            if (PageIdentity::readId($path) === null) $missing[] = $path;
        }
        sort($missing);
        foreach ($missing as $path) {
            echo ($apply ? 'REGISTER ' : 'PENDING  ') . substr($path, strlen($root) + 1) . "\n";
            if (!$apply) continue;
            $raw = file_get_contents($path);
            if ($raw === false) throw new RuntimeException('Cannot read ' . $path);
            do { $id = PageIdentity::generate(); } while (isset($ids[$id]));
            $updated = PageIdentity::withId($raw, $id);
            $temp = tempnam(dirname($path), '.homehub-id-');
            if ($temp === false) throw new RuntimeException('Cannot create temporary file');
            try {
                if (file_put_contents($temp, $updated, LOCK_EX) === false) throw new RuntimeException('Cannot write temporary file');
                chmod($temp, fileperms($path) & 0777);
                if (!rename($temp, $path)) throw new RuntimeException('Cannot update ' . $path);
            } finally { if (is_file($temp)) unlink($temp); }
            $ids[$id] = substr($path, strlen($root) + 1);
        }
        echo count($missing) . ($apply ? " pages registered\n" : " pages without IDs (dry run)\n");
    });
} catch (Throwable $e) {
    fwrite(STDERR, "Registration stopped: " . $e->getMessage() . "\n");
    exit(1);
}
