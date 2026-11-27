<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace Symfony\Component\Yaml {
    final class Yaml {
        public static function parse(string $text): array {
            $result = [];
            foreach (explode("\n", $text) as $line) {
                if (preg_match('/^([a-z-]+):\s*(.*)$/', $line, $m)) $result[$m[1]] = $m[2];
            }
            return $result;
        }
    }
}

namespace League\CommonMark { final class MarkdownConverter {} }
namespace HomeHub { final class AlignedMarkdown { public static function converter(): \League\CommonMark\MarkdownConverter { return new \League\CommonMark\MarkdownConverter(); } } }
namespace {
    foreach (['Page','PageRepository','PermissionService','TagRepository'] as $class) require __DIR__ . '/../src/lib/' . $class . '.php';
    require __DIR__ . '/../src/lib/PageIdentity.php';
    require __DIR__ . '/../src/lib/MediaOperationLock.php';
    require __DIR__ . '/../src/lib/PageEditLease.php';
    require __DIR__ . '/../src/lib/DocumentBody.php';
    require __DIR__ . "/../src/lib/PageMetadata.php";
    require __DIR__ . '/../src/lib/Admin.php';
    $base = sys_get_temp_dir() . '/homehub-page004-' . bin2hex(random_bytes(6));
    mkdir($base . '/content', 0700, true);
    mkdir($base . '/config', 0700, true);
    mkdir($base . '/content/ordner');
    try {
        $id = \HomeHub\PageIdentity::generate();
        $original = "---\nid: $id\ntitle: Test\n---\nInhalt\n";
        file_put_contents($base . '/content/start.md', $original);
        $reflection = new \ReflectionClass(\HomeHub\Admin::class);
        $admin = $reflection->newInstanceWithoutConstructor();
        foreach (['contentDir' => $base . '/content', 'dataDir' => $base . '/config'] as $property => $value) {
            $reflection->getProperty($property)->setValue($admin, $value);
        }
        $admin->move('start', 'ordner/neu');
        if (file_get_contents($base . '/content/ordner/neu.md') !== $original) throw new \RuntimeException('Move changed content or ID');
        if (file_exists($base . '/content/start.md')) throw new \RuntimeException('Source still exists');
        file_put_contents($base . '/content/ordner/konflikt.md', 'existing');
        try { $admin->move('ordner/neu', 'ordner/konflikt'); throw new \RuntimeException('Conflict not rejected'); }
        catch (\RuntimeException $e) { if ($e->getMessage() === 'Conflict not rejected') throw $e; }
        if (file_get_contents($base . '/content/ordner/konflikt.md') !== 'existing') throw new \RuntimeException('Target overwritten');
        try { $admin->move('ordner/neu', 'ordner/neu2', str_repeat('0', 64)); throw new \RuntimeException('Stale revision not rejected'); }
        catch (\RuntimeException $e) { if ($e->getMessage() === 'Stale revision not rejected') throw $e; }
        if (!file_exists($base . '/content/ordner/neu.md')) throw new \RuntimeException('Source lost on stale revision');
        $root = $base . '/content';
        $write = static function ($slug, $visibility = 'all') use ($root) {
            $raw = "---\nid: " . \HomeHub\PageIdentity::generate() . "\ntitle: Test\nvisibility: $visibility\n---\nBody\n";
            file_put_contents($root . '/' . $slug . '.md', $raw); return $raw;
        };
        $reject = static function ($fn) { try { $fn(); } catch (\RuntimeException $e) { return; } throw new \RuntimeException('Expected rejection'); };
        mkdir($root . '/a/deep', 0700, true); mkdir($root . '/dest');
        $a = $write('a', 'authenticated'); $b = $write('a/deep/b'); $write('dest');
        $rev = $admin->moveSnapshot('a')['revision'];
        $write('a/deep/denied', 'roles');
        $deniedRev = $admin->moveSnapshot('a')['revision'];
        $reject(fn()=> $admin->move('a','dest/new','',true,$deniedRev,'editor','editor'));
        unlink($root . '/a/deep/denied.md');
        $write('dest','roles');
        $reject(fn()=> $admin->move('a','dest/new','',true,$rev,'editor','editor'));
        $write('dest');
        file_put_contents($root . '/a/deep/b.md', $b . 'changed');
        $reject(fn()=> $admin->move('a','dest/new','',true,$rev));
        file_put_contents($root . '/a/deep/b.md', $b);
        symlink($root . '/dest', $root . '/a/link');
        $reject(fn()=> $admin->move('a','dest/new','',true,$rev));
        unlink($root . '/a/link');
        $reject(fn()=> $admin->move('a','dest/new'));
        $reject(fn()=> $admin->move('a','a/deep/new','',true,$rev));
        $reject(fn()=> $admin->move('a','dest/new','',true,'stale'));
        $reject(fn()=> $admin->move('a','dest/new','',true,$rev,'reader'));
        $lease = new \HomeHub\PageEditLease($base . '/config'); $token = $lease->acquire('a/deep/b','editor');
        $reject(fn()=> $admin->move('a','dest/new','',true,$rev)); $lease->release('a/deep/b','editor',$token);
        mkdir($root . '/dest/new'); $reject(fn()=> $admin->move('a','dest/new','',true,$rev)); rmdir($root . '/dest/new');
        $admin->move('a','dest/new','',true,$rev,'editor','editor');
        if (file_get_contents($root . '/dest/new.md') !== $a || file_get_contents($root . '/dest/new/deep/b.md') !== $b || file_exists($root . '/a')) throw new \RuntimeException('Tree move failed');
        $admin->move('dest/new','dest/renamed','',true,$admin->moveSnapshot('dest/new')['revision']);
        if (!is_file($root . '/dest/renamed/deep/b.md')) throw new \RuntimeException('Tree rename failed');
        echo "OK: PAGE-004 move, stable ID, conflict, stale revision; tree move, rename, ACL, lease\n";
    } finally {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $entry) {
            if ($entry->isDir() && !$entry->isLink()) rmdir($entry->getPathname()); else unlink($entry->getPathname());
        }
        rmdir($base);
    }
}
