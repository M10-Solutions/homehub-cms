<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

/** PAGE-003: conservative, two-phase migration of legacy HomeHub page URLs. */
$project = dirname(__DIR__);
$autoload = $project . '/src/vendor/autoload.php';
if (!is_file($autoload)) $autoload = $project . '/vendor/autoload.php';
if (!is_file($autoload)) { fwrite(STDERR, "Composer dependencies missing. Run in the HomeHub container.\n"); exit(2); }
require $autoload;
require_once $project . '/src/lib/PageIdentity.php';
require_once $project . '/src/lib/MediaOperationLock.php';

use HomeHub\PageIdentity;
use HomeHub\MediaOperationLock;

$args = array_slice($argv, 1);
if (count($args) !== 1 || !in_array($args[0], ['--check', '--apply'], true)) {
    fwrite(STDERR, "Usage: php bin/migrate-page-links.php --check|--apply\n"); exit(2);
}
$apply = $args[0] === '--apply';
$content = $project . '/data/content';
$stateDir = $project . '/data/config/page-link-migration';
$manifestFile = $stateDir . '/check.json';

/** Normalize a page target without allowing traversal outside the content root. */
function pageTargetSlug(string $path, string $relativeSource): ?string
{
    $absolute = str_starts_with($path, '/');
    $base = $absolute ? [] : explode('/', str_replace('\\', '/', dirname($relativeSource)));
    if ($base === ['.']) $base = [];
    $segments = $base;
    foreach (explode('/', rawurldecode($path)) as $segment) {
        if ($segment === '' || $segment === '.') continue;
        if ($segment === '..') {
            if (!$segments) return null;
            array_pop($segments);
            continue;
        }
        if (!preg_match('/\A[a-zA-Z0-9_-]+(?:\.md)?\z/D', $segment)) return null;
        $segments[] = $segment;
    }
    $slug = implode('/', $segments);
    if (str_ends_with(strtolower($slug), '.md')) $slug = substr($slug, 0, -3);
    return strtolower($slug);
}

/** Only literal Markdown inline links and reference definitions; skip fenced code, inline code, and front matter. */
function migrateLinks(string $source, array $slugs, string $relativeSource, array &$stats): string
{
    $lines = preg_split('/(?<=\n)/', $source);
    if ($lines === false) throw new RuntimeException('Cannot split Markdown');
    $front = isset($lines[0]) && trim($lines[0]) === '---';
    $fence = null;
    foreach ($lines as &$line) {
        if ($front) {
            if (isset($frontStarted)) { if (trim($line) === '---') $front = false; }
            else $frontStarted = true;
            continue;
        }
        if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $fm)) {
            $symbol = $fm[1][0]; $length = strlen($fm[1]);
            if ($fence === null) $fence = [$symbol, $length];
            elseif ($fence[0] === $symbol && $length >= $fence[1]) $fence = null;
            continue;
        }
        if ($fence !== null || preg_match('/^(?: {4}|\t)/', $line)) continue;
        // Protect inline code spans and HTML tags from URL replacement.
        $parts = preg_split('/(`+[^`\r\n]*`+|<[^>\r\n]+>)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) continue;
        foreach ($parts as $i => &$part) {
            if ($i % 2 !== 0) continue;
            $part = preg_replace_callback('~(?<!!)\[[^\]\r\n]+\]\(\s*(<)?([^\s)>]+)(?(1)>)([^)]*)\)|^\s{0,3}\[[^\]\r\n]+\]:\s*(<)?([^\s>]+)(?(5)>)~m',
                static function (array $m) use ($slugs, $relativeSource, &$stats): string {
                    $whole = $m[0];
                    $target = isset($m[6]) && $m[6] !== '' ? $m[6] : ($m[2] ?? '');
                    if ($target === '') return $whole;
                    $decoded = html_entity_decode($target, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $url = parse_url($decoded);
                    if ($url === false || isset($url['scheme']) || isset($url['host'])) return $whole;
                    $path = $url['path'] ?? '';
                    $query = [];
                    parse_str($url['query'] ?? '', $query);
                    $legacyQuery = isset($query['page']) && is_string($query['page'])
                        && in_array($path, ['', '/', '/index.php', 'index.php'], true);
                    $relativeMd = !str_starts_with($path, '/') && preg_match('~\.md$~i', $path);
                    $absoluteWiki = str_starts_with($path, '/') && !str_contains(basename($path), '.')
                        && !in_array($path, ['/', '/index.php', '/page.php'], true);
                    if (!$legacyQuery && !$relativeMd && !$absoluteWiki) return $whole;
                    if (isset($url['fragment']) || ($legacyQuery && count($query) !== 1)
                        || (!$legacyQuery && isset($url['query']))) {
                        $stats['warnings'][] = "Additional query or fragment: $target";
                        return $whole;
                    }
                    $candidate = $legacyQuery ? '/' . ltrim($query['page'], '/') : $path;
                    $slug = pageTargetSlug($candidate, $relativeSource);
                    if ($slug === null || $slug === '') {
                        $stats['warnings'][] = "Unsafe page path: $target";
                        return $whole;
                    }
                    if (!isset($slugs[$slug])) {
                        $stats['warnings'][] = "Unknown page target: $target (resolved: $slug)";
                        return $whole;
                    }
                    $replacement = '/page.php?id=' . $slugs[$slug];
                    $stats['internal'][] = [$target, $replacement];
                    return str_replace($target, $replacement, $whole);
                }, $part) ?? $part;
        }
        unset($part);
        $line = implode('', $parts);
    }
    unset($line);
    return implode('', $lines);
}

try {
    (new MediaOperationLock($project . '/data'))->run(static function () use ($content, $stateDir, $manifestFile, $apply): void {
        $root = realpath($content);
        if ($root === false) throw new RuntimeException('Content directory missing');
        $inventory = PageIdentity::inventory($root);
        $slugs = [];
        foreach ($inventory as $id => $relative) {
            $slug = strtolower(str_replace('\\', '/', substr($relative, 0, -3)));
            if (isset($slugs[$slug])) throw new RuntimeException('Ambiguous slug: ' . $slug);
            $slugs[$slug] = $id;
        }
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry->isFile() || $entry->isLink() || strtolower($entry->getExtension()) !== 'md') continue;
            $real = realpath($entry->getPathname());
            if ($real === false || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) continue;
            $rel = substr($real, strlen($root) + 1);
            $raw = file_get_contents($real);
            if ($raw === false) throw new RuntimeException('Unreadable page: ' . $rel);
            $stats = ['internal' => [], 'warnings' => []];
            $new = migrateLinks($raw, $slugs, $rel, $stats);
            $files[$rel] = ['sha256' => hash('sha256', $raw), 'new_sha256' => hash('sha256', $new), 'links' => $stats['internal'], 'warnings' => $stats['warnings']];
        }
        ksort($files);
        $plan = ['version' => 2, 'files' => $files];
        $changes = array_filter($files, static fn ($f) => $f['sha256'] !== $f['new_sha256']);
        $warnings = array_sum(array_map(static fn ($f) => count($f['warnings']), $files));
        foreach ($files as $rel => $info) {
            foreach ($info['links'] as $pair) echo "CHANGE $rel: {$pair[0]} => {$pair[1]}\n";
            foreach ($info['warnings'] as $warning) echo "WARN   $rel: $warning\n";
        }
        echo count($files) . ' pages, ' . count($changes) . ' files to change, ' . $warnings . " warnings\n";
        if (!$apply) {
            if (!is_dir($stateDir) && !mkdir($stateDir, 0700, true) && !is_dir($stateDir)) throw new RuntimeException('Cannot create state directory');
            $tmp = tempnam($stateDir, '.check-');
            if ($tmp === false) throw new RuntimeException('Cannot create manifest');
            try {
                if (file_put_contents($tmp, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot write manifest');
                chmod($tmp, 0600);
                if (!rename($tmp, $manifestFile)) throw new RuntimeException('Cannot store manifest');
            } finally { if (is_file($tmp)) unlink($tmp); }
            echo "Dry run completed. Review warnings, then run --apply.\n";
            return;
        }
        if (!is_file($manifestFile)) throw new RuntimeException('No check manifest: run --check first');
        $saved = json_decode((string) file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR);
        if ($saved !== $plan) throw new RuntimeException('Pages or targets changed since --check. Run --check again.');
        if ($warnings > 0) echo "Warning: unresolved links remain unchanged.\n";
        if (!$changes) { echo "Nothing to migrate.\n"; return; }
        $backup = $stateDir . '/backup-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
        if (!mkdir($backup, 0700)) throw new RuntimeException('Cannot create backup directory');
        // Backup ALL affected pages before touching any original.
        foreach ($changes as $rel => $info) {
            $src = $root . '/' . $rel;
            $dst = $backup . '/' . $rel;
            if (!is_dir(dirname($dst)) && !mkdir(dirname($dst), 0700, true) && !is_dir(dirname($dst))) throw new RuntimeException('Cannot create backup subdirectory');
            if (!copy($src, $dst)) throw new RuntimeException('Backup failed: ' . $rel);
            if (hash_file('sha256', $dst) !== $info['sha256']) throw new RuntimeException('Backup verification failed: ' . $rel);
        }
        foreach ($changes as $rel => $info) {
            $src = $root . '/' . $rel;
            $raw = file_get_contents($src);
            if ($raw === false || hash('sha256', $raw) !== $info['sha256']) throw new RuntimeException('File changed during migration: ' . $rel);
            $stats = ['internal' => [], 'warnings' => []];
            $new = migrateLinks($raw, $slugs, $rel, $stats);
            if (hash('sha256', $new) !== $info['new_sha256']) throw new RuntimeException('Migration plan mismatch: ' . $rel);
            $tmp = tempnam(dirname($src), '.homehub-migrate-');
            if ($tmp === false) throw new RuntimeException('Cannot create temporary file: ' . $rel);
            try {
                if (file_put_contents($tmp, $new, LOCK_EX) !== strlen($new)) throw new RuntimeException('Write failed: ' . $rel);
                chmod($tmp, fileperms($src) & 0777);
                if (!rename($tmp, $src)) throw new RuntimeException('Rename failed: ' . $rel);
            } finally { if (is_file($tmp)) unlink($tmp); }
            echo "MIGRATED $rel\n";
        }
        unlink($manifestFile);
        echo "Completed. Backup: $backup\n";
    });
} catch (Throwable $e) {
    fwrite(STDERR, "Migration stopped: " . $e->getMessage() . "\n");
    exit(1);
}
