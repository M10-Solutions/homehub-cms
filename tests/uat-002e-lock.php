<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
require __DIR__ . '/../src/lib/PageEditLease.php';
require __DIR__ . '/../src/lib/MediaOperationLock.php';
// Only the slug validator is needed for this dependency-free lease test.
require __DIR__ . '/../src/lib/Admin.php';
$dir = sys_get_temp_dir() . '/homehub-uat002e-' . bin2hex(random_bytes(5));
mkdir($dir);
try {
    $lease = new HomeHub\PageEditLease($dir);
    $mutex = new HomeHub\MediaOperationLock($dir);
    $token = $mutex->run(fn() => $lease->acquire('haus/heizung', 'editor-a'));
    $lease->assertOwner('haus/heizung', 'editor-a', $token);
    try { $lease->acquire('haus/heizung', 'editor-b'); throw new RuntimeException('Second editor acquired lease'); }
    catch (RuntimeException $e) { if ($e->getMessage() === 'Second editor acquired lease') throw $e; }
    try { $lease->release('haus/heizung', 'editor-b', $token); throw new RuntimeException('Other editor released lease'); }
    catch (RuntimeException $e) { if ($e->getMessage() === 'Other editor released lease') throw $e; }
    $lease->heartbeat('haus/heizung', 'editor-a', $token);
    $lease->release('haus/heizung', 'editor-a', $token);
    $second = $lease->acquire('haus/heizung', 'editor-b');
    if ($second === $token) throw new RuntimeException('Lease token reused');
    try { $lease->heartbeat('haus/heizung', 'editor-a', $token); throw new RuntimeException('Old token accepted'); }
    catch (RuntimeException $e) { if ($e->getMessage() === 'Old token accepted') throw $e; }
    $recordPath = $dir . '/page-edit-' . hash('sha256', 'haus/heizung') . '.json';
    $record = json_decode(file_get_contents($recordPath), true);
    $record['expires'] = time() - 1;
    file_put_contents($recordPath, json_encode($record));
    if ($lease->current('haus/heizung') !== null) throw new RuntimeException('Expired lease still active');
    try { $lease->heartbeat('haus/heizung', 'editor-b', $second); throw new RuntimeException('Expired lease revived'); }
    catch (RuntimeException $e) { if ($e->getMessage() === 'Expired lease revived') throw $e; }
    $lease->acquire('haus/heizung', 'editor-a');
    echo "PASS UAT-002e lease lifecycle and expiry\n";
} finally {
    foreach (glob($dir . '/*') ?: [] as $file) @unlink($file);
    @rmdir($dir);
}
