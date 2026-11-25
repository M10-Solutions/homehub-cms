<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

/** MEDIA-004: explicit two-phase migration of legacy media links in Markdown pages. */
$project = dirname(__DIR__);
require_once $project . '/src/lib/MediaOperationLock.php';
require_once $project . '/src/lib/MediaLibrary.php';

$args = array_slice($argv, 1);
if (count($args) !== 1 || !in_array($args[0], ['--check', '--apply'], true)) {
    fwrite(STDERR, "Usage: php bin/migrate-media-links.php --check|--apply\n"); exit(2);
}
$apply = $args[0] === '--apply';
$contentDir = $project . '/data/content';
$uploadDir = $project . '/data/upload';
$configDir = $project . '/data/config';
$stateDir = $configDir . '/media-link-migration';
$manifest = $stateDir . '/check.json';

/** @return array<string,string> */
function mediaInventory(string $registryFile, string $uploadDir): array
{
    if (!is_file($registryFile)) throw new RuntimeException('Media registry missing: ' . $registryFile);
    $raw = file_get_contents($registryFile);
    $data = json_decode((string)$raw, true, 512, JSON_THROW_ON_ERROR);
    if (($data['version'] ?? null) !== 1 || !is_array($data['items'] ?? null)) throw new RuntimeException('Invalid media registry');
    $paths = [];
    foreach ($data['items'] as $id => $item) {
        $path = $item['path'] ?? null;
        if (!is_string($id) || !preg_match('/\A[a-f0-9]{32}\z/D', $id) || !is_string($path)
            || !\HomeHub\MediaLibrary::validRelativePath($path)) throw new RuntimeException('Invalid registry entry');
        if (isset($paths[$path])) throw new RuntimeException('Duplicate registered path: ' . $path);
        // Do not migrate references to files that are no longer present.
        $file = $uploadDir . '/' . $path;
        if (is_link($file) || !is_file($file)) continue;
        $paths[$path] = $id;
    }
    ksort($paths);
    return $paths;
}

/** Resolve only explicit legacy media URLs; do not guess arbitrary relative filenames. */
function mediaTarget(string $target): ?string
{
    $url = html_entity_decode($target, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $parts = parse_url($url);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) return null;
    $path = $parts['path'] ?? '';
    $query = [];
    parse_str($parts['query'] ?? '', $query);
    if (in_array($path, ['/media.php', 'media.php'], true)) {
        if (isset($query['id']) || !isset($query['file']) || !is_string($query['file']) || count($query) !== 1) return null;
        $relative = $query['file'];
    } elseif (preg_match('~\A(?:/?|\.\./|\./)*(?:data/upload/|uploads?/)(.+)\z~i', $path, $m)) {
        if ($query !== []) return null;
        $relative = rawurldecode($m[1]);
    } else return null;
    if (isset($parts['fragment']) || !\HomeHub\MediaLibrary::validRelativePath($relative)) return null;
    return $relative;
}

/** @param array<string,string> $paths */
function convertMedia(string $source, array $paths, array &$changes, array &$warnings): string
{
    $lines = preg_split('/(?<=\n)/', $source);
    if ($lines === false) throw new RuntimeException('Cannot split Markdown');
    $front = isset($lines[0]) && trim($lines[0]) === '---';
    $fence = null;
    foreach ($lines as $n => &$line) {
        if ($front) {
            if ($n !== 0 && trim($line) === '---') $front = false;
            continue;
        }
        if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $fm)) {
            $symbol = $fm[1][0]; $length = strlen($fm[1]);
            if ($fence === null) $fence = [$symbol, $length];
            elseif ($fence[0] === $symbol && $length >= $fence[1]) $fence = null;
            continue;
        }
        if ($fence !== null || preg_match('/^(?: {4}|\t)/', $line)) continue;
        // Code spans are not media links. HTML <img> tags are handled separately.
        $pieces = preg_split('/(`+[^`\r\n]*`+)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($pieces === false) continue;
        foreach ($pieces as $i => &$piece) {
            if ($i % 2) continue;
            $replace = static function (string $url) use ($paths, &$changes, &$warnings): ?string {
                $path = mediaTarget($url);
                if ($path === null) return null;
                if (!isset($paths[$path])) { $warnings[] = "Unknown media target: $url"; return null; }
                $new = '/media.php?id=' . $paths[$path];
                $changes[] = [$url, $new];
                return $new;
            };
            // Markdown inline images AND file links, including optional title.
            $piece = preg_replace_callback('~(!?\[[^\]\r\n]*\]\(\s*<?)([^\s)>]+)(>?[^)]*\))~',
                static function ($m) use ($replace) { $new = $replace($m[2]); return $new === null ? $m[0] : $m[1] . $new . $m[3]; }, $piece) ?? $piece;
            // Markdown reference-style link definitions.
            $piece = preg_replace_callback('~(^\s{0,3}\[[^\]\r\n]+\]:\s*<?)([^\s>]+)(>?[^\r\n]*)~m',
                static function ($m) use ($replace) { $new = $replace($m[2]); return $new === null ? $m[0] : $m[1] . $new . $m[3]; }, $piece) ?? $piece;
            // HTML image source; do not modify arbitrary HTML attributes or text.
            $piece = preg_replace_callback('~<img\b[^>]*>~i', static function ($m) use ($replace) {
                return preg_replace_callback('~(\bsrc\s*=\s*)(["\'])(.*?)\2~i',
                    static function ($attr) use ($replace) { $new = $replace($attr[3]); return $new === null ? $attr[0] : $attr[1] . $attr[2] . $new . $attr[2]; }, $m[0]) ?? $m[0];
            }, $piece) ?? $piece;
        }
        unset($piece);
        $line = implode('', $pieces);
    }
    unset($line);
    return implode('', $lines);
}

try {
    (new \HomeHub\MediaOperationLock($configDir))->run(static function () use ($contentDir, $uploadDir, $configDir, $stateDir, $manifest, $apply): void {
        $root = realpath($contentDir);
        if ($root === false) throw new RuntimeException('Content directory missing');
        $paths = mediaInventory($configDir . '/media-index.json', $uploadDir);
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if ($entry->isLink()) throw new RuntimeException('Symlink in wiki content: ' . $entry->getPathname());
            if (!$entry->isFile() || strtolower($entry->getExtension()) !== 'md') continue;
            $rel = substr($entry->getPathname(), strlen($root) + 1);
            $raw = file_get_contents($entry->getPathname());
            if ($raw === false) throw new RuntimeException('Unreadable page: ' . $rel);
            $changes = []; $warnings = [];
            $new = convertMedia($raw, $paths, $changes, $warnings);
            $files[$rel] = ['sha256'=>hash('sha256', $raw), 'new_sha256'=>hash('sha256', $new), 'changes'=>$changes, 'warnings'=>$warnings];
        }
        ksort($files);
        $plan = ['version'=>1, 'registry'=>hash('sha256', (string)file_get_contents($configDir . '/media-index.json')), 'files'=>$files];
        $changed = array_filter($files, static fn ($v) => $v['sha256'] !== $v['new_sha256']);
        $warningCount = 0; $linkCount = 0;
        foreach ($files as $rel => $info) {
            foreach ($info['changes'] as $change) { echo "CHANGE $rel: {$change[0]} => {$change[1]}\n"; $linkCount++; }
            foreach ($info['warnings'] as $warning) { echo "WARN   $rel: $warning\n"; $warningCount++; }
        }
        echo count($files) . " pages, $linkCount links, " . count($changed) . " files to change, $warningCount warnings\n";
        if (!$apply) {
            if (!is_dir($stateDir) && !mkdir($stateDir, 0700, true) && !is_dir($stateDir)) throw new RuntimeException('Cannot create state directory');
            $tmp = tempnam($stateDir, '.check-');
            if ($tmp === false) throw new RuntimeException('Cannot create manifest');
            try {
                if (file_put_contents($tmp, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot write manifest');
                chmod($tmp, 0600);
                if (!rename($tmp, $manifest)) throw new RuntimeException('Cannot save manifest');
            } finally { if (is_file($tmp)) unlink($tmp); }
            echo "Dry run completed. Review warnings, then run --apply.\n";
            return;
        }
        if (!is_file($manifest)) throw new RuntimeException('No check manifest; run --check first');
        $saved = json_decode((string)file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
        if ($saved !== $plan) throw new RuntimeException('Pages or media registry changed since --check. Run --check again.');
        if (!$changed) { echo "Nothing to migrate.\n"; return; }
        $backup = $stateDir . '/backup-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
        if (!mkdir($backup, 0700)) throw new RuntimeException('Cannot create backup directory');
        foreach ($changed as $rel => $info) {
            $dst = $backup . '/' . $rel;
            if (!is_dir(dirname($dst)) && !mkdir(dirname($dst), 0700, true) && !is_dir(dirname($dst))) throw new RuntimeException('Cannot create backup directory for ' . $rel);
            if (!copy($root . '/' . $rel, $dst) || hash_file('sha256', $dst) !== $info['sha256']) throw new RuntimeException('Backup verification failed: ' . $rel);
        }
        foreach ($changed as $rel => $info) {
            $src = $root . '/' . $rel;
            $raw = file_get_contents($src);
            if ($raw === false || hash('sha256', $raw) !== $info['sha256']) throw new RuntimeException('File changed during migration: ' . $rel);
            $changes = []; $warnings = [];
            $new = convertMedia($raw, $paths, $changes, $warnings);
            if (hash('sha256', $new) !== $info['new_sha256']) throw new RuntimeException('Migration plan mismatch: ' . $rel);
            $tmp = tempnam(dirname($src), '.media-migrate-');
            if ($tmp === false) throw new RuntimeException('Cannot create temporary file: ' . $rel);
            try {
                if (file_put_contents($tmp, $new, LOCK_EX) !== strlen($new)) throw new RuntimeException('Cannot write: ' . $rel);
                chmod($tmp, fileperms($src) & 0777);
                if (!rename($tmp, $src)) throw new RuntimeException('Cannot replace: ' . $rel);
            } finally { if (is_file($tmp)) unlink($tmp); }
            echo "MIGRATED $rel\n";
        }
        unlink($manifest);
        echo "Completed. Backup: $backup\n";
    });
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration stopped: ' . $e->getMessage() . "\n");
    exit(1);
}
