<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

/** Run isolated CLI regression tests; never touch production data/. */
$root = dirname(__DIR__);
$strict = in_array('--strict', $argv, true);
$checks = [
    'PHP >= 8.3' => PHP_VERSION_ID >= 80300,
    'proc_open' => function_exists('proc_open'),
    'Temporary directory writable' => is_writable(sys_get_temp_dir()),
];
foreach (['dom', 'zip', 'mbstring', 'fileinfo'] as $extension) {
    $checks['PHP extension ' . $extension] = extension_loaded($extension);
}
try {
    $autoload = $root . '/vendor/autoload.php';
    $checks['Composer autoload'] = is_file($autoload);
    if ($checks['Composer autoload']) require_once $autoload;
    $checks['CommonMark'] = class_exists(League\CommonMark\Environment\Environment::class);
    $checks['Symfony YAML'] = class_exists(Symfony\Component\Yaml\Yaml::class);
} catch (Throwable $e) {
    $checks['Composer dependencies loadable'] = false;
    fwrite(STDERR, $e->getMessage() . "\n");
}
echo "HomeHub QA-002 | PHP " . PHP_VERSION . ($strict ? ' | strict' : ' | local') . "\nDependency checks\n";
foreach ($checks as $name => $ok) echo ($ok ? 'OK      ' : 'MISSING ') . $name . "\n";
if (($strict && in_array(false, $checks, true)) || !$checks['proc_open'] || !$checks['Temporary directory writable']) {
    fwrite(STDERR, "Preflight failed; no tests executed.\n");
    exit(2);
}
if (in_array(false, $checks, true)) echo "WARNING: local run may omit integration checks. Use --strict for acceptance.\n";
$tests = [
    'page-identity.php', 'page-link-id.php', 'page-004-move.php',
    'media-operation-lock.php', 'page-media-lock.php',
    'migrate-page-links.php', 'media-004-migration.php',
    'export-002-odt.php', 'admin-001b-folders.php', 'admin-001d-cleanup.php', 'media-006-batch.php', 'user-001.php', 'ui-001.php', 'ui-005.php', 'ui-006.php', 'ui-007.php', 'ui-008.php', 'ui-009.php', 'export-003-zip.php', 'tag-001.php', 'tag-003.php', 'tag-004.php', 'search-001.php', 'cleanup-001.php', 'qa-002.php', 'version-001.php', 'ui-011.php', 'ui-012-nav-theme.php', 'uat-002a.php', 'uat-002a-followup.php', 'uat-002c.php', 'uat-edt-01.php', 'uat-002d.php', 'uat-002b.php', 'security-metadata.php',
];
$start = microtime(true);
$failed = [];
echo "\nRegression tests\n";
foreach ($tests as $name) {
    $time = microtime(true);
    $file = $root . '/tests/' . $name;
    // A shared temporary output file avoids deadlocks on full stdout/stderr pipes.
    $output = tmpfile();
    if (!$output) { fwrite(STDERR, "Cannot create test log\n"); exit(2); }
    $proc = is_file($file) ? proc_open([PHP_BINARY, $file], [0 => ['file', '/dev/null', 'r'], 1 => $output, 2 => $output], $pipes, $root) : false;
    $status = is_resource($proc) ? proc_close($proc) : 1;
    rewind($output);
    $log = stream_get_contents($output);
    fclose($output);
    printf("%-4s %-30s %6.2fs\n", $status === 0 ? 'PASS' : 'FAIL', $name, microtime(true) - $time);
    if ($status !== 0) $failed[] = $name;
    if ($log !== '') echo '  ' . str_replace("\n", "\n  ", rtrim($log)) . "\n";
    if (!is_resource($proc) && !is_file($file)) echo "  Missing test file\n";
}
printf("\n%d/%d tests passed | %d failed | %.2fs\n", count($tests)-count($failed), count($tests), count($failed), microtime(true)-$start);
if ($failed) echo 'Failed: ' . implode(', ', $failed) . "\n";
exit($failed ? 1 : 0);
