<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root = dirname(__DIR__);
$compose = is_file($root . '/compose.yaml') ? file_get_contents($root . '/compose.yaml') : false;
// compose.yaml is not part of the runtime image; validate it when available on host.
if ($compose !== false) {
    $qa = explode("\n  qa:\n", $compose, 2)[1] ?? '';
    foreach (['read_only: true', 'network_mode: none', 'command: [--strict]', 'user: www-data', '/tmp:mode=1777'] as $setting) {
        if (!str_contains($qa, $setting)) throw new RuntimeException('Missing QA isolation: ' . $setting);
    }
    if (str_contains($qa, './data:')) throw new RuntimeException('QA mounts production data');
}
// Exercise the real strict preflight with PHP configuration disabled.
$log = tmpfile();
$proc = proc_open([PHP_BINARY, '-n', $root . '/bin/run-regression-tests.php', '--strict'], [0 => ['file', '/dev/null', 'r'], 1 => $log, 2 => $log], $pipes, $root);
if (!is_resource($proc)) throw new RuntimeException('Cannot start preflight test');
$status = proc_close($proc);
rewind($log); $result = stream_get_contents($log); fclose($log);
if ($status !== 2 || !str_contains($result, 'no tests executed')) throw new RuntimeException('Strict preflight did not reject missing dependencies');
echo "Strict preflight rejects incomplete PHP environment\n";
