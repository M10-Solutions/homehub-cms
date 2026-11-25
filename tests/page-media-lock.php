<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
require dirname(__DIR__) . '/src/lib/MediaOperationLock.php';
use HomeHub\MediaOperationLock;
$root = sys_get_temp_dir() . '/homehub-page-lock-' . bin2hex(random_bytes(6));
mkdir($root);
try {
    $lock = new MediaOperationLock($root);
    $child = <<<'PHP'
require $argv[1];
(new \HomeHub\MediaOperationLock($argv[2]))->run(function () use ($argv) {
    file_put_contents($argv[3], 'entered');
});
PHP;
    $marker = $root . '/entered';
    $cmd = [PHP_BINARY, '-r', $child, dirname(__DIR__) . '/src/lib/MediaOperationLock.php', $root, $marker];
    $lock->run(function () use ($cmd, $marker): void {
        $proc = proc_open($cmd, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes);
        if (!is_resource($proc)) throw new RuntimeException('Cannot start competing process');
        try {
            usleep(250000);
            if (file_exists($marker)) throw new RuntimeException('Second process entered locked section');
        } finally {
            // Process remains blocked until the outer lock is released.
            foreach ($pipes as $pipe) fclose($pipe);
            // Closing process pipes does not release the filesystem lock.
            $GLOBALS['lock_test_proc'] = $proc;
        }
    });
    $proc = $GLOBALS['lock_test_proc'];
    $exit = proc_close($proc);
    if ($exit !== 0 || !is_file($marker)) throw new RuntimeException('Waiting process did not proceed after release');
    echo "OK: competing process waits for shared media operation lock\n";
} finally {
    foreach (glob($root . '/*') ?: [] as $file) unlink($file);
    rmdir($root);
}
