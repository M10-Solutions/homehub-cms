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

namespace {
    require __DIR__ . '/../src/lib/PageIdentity.php';
    require __DIR__ . '/../src/lib/MediaOperationLock.php';
    require __DIR__ . '/../src/lib/PageEditLease.php';
    require __DIR__ . '/../src/lib/DocumentBody.php';
    require __DIR__ . "/../src/lib/PageMetadata.php";
    require __DIR__ . '/../src/lib/Admin.php';
    $base = sys_get_temp_dir() . '/homehub-page004-' . bin2hex(random_bytes(6));
    mkdir($base . '/content', 0700, true);
    mkdir($base . '/config', 0700, true);
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
        echo "OK: PAGE-004 move, stable ID, conflict, stale revision\n";
    } finally {
        foreach (glob($base . '/content/ordner/*') ?: [] as $f) unlink($f);
        foreach (glob($base . '/content/*') ?: [] as $f) if (is_file($f)) unlink($f);
        @rmdir($base . '/content/ordner');
        @unlink($base . '/config/media-operations.lock');
        @rmdir($base . '/content'); @rmdir($base . '/config'); @rmdir($base);
    }
}
