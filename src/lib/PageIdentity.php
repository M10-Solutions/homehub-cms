<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

use Symfony\Component\Yaml\Yaml;

/** Stable page identity stored in YAML front matter; no external index. */
final class PageIdentity
{
    public static function valid(mixed $id): bool
    {
        return is_string($id) && (bool) preg_match('/\Ap_[a-f0-9]{32}\z/D', $id);
    }

    public static function generate(): string
    {
        return 'p_' . bin2hex(random_bytes(16));
    }

    /** @return array<string, string> Map IDs to relative paths; reject ambiguous IDs. */
    public static function inventory(string $contentDir): array
    {
        $result = [];
        $root = realpath($contentDir);
        if ($root === false) throw new \RuntimeException('Inhaltsordner fehlt.');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry->isFile() || $entry->isLink() || strtolower($entry->getExtension()) !== 'md') continue;
            $path = realpath($entry->getPathname());
            if ($path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) continue;
            $id = self::readId($path);
            if ($id === null) continue;
            if (isset($result[$id])) throw new \RuntimeException('Doppelte Seiten-ID: ' . $id . ' (' . $result[$id] . ', ' . $path . ')');
            $result[$id] = substr($path, strlen($root) + 1);
        }
        return $result;
    }

    public static function readId(string $path): ?string
    {
        $raw = file_get_contents($path);
        if ($raw === false) throw new \RuntimeException('Seite konnte nicht gelesen werden.');
        if (!preg_match('/\A---\R(.*?)\R---(?:\R|\z)/s', $raw, $matches)) return null;
        try { $meta = Yaml::parse($matches[1]); }
        catch (\Throwable $e) { throw new \RuntimeException('Ungültiges YAML: ' . $path, 0, $e); }
        if (!is_array($meta) || !array_key_exists('id', $meta)) return null;
        if (!self::valid($meta['id'])) throw new \RuntimeException('Ungültige Seiten-ID: ' . $path);
        return $meta['id'];
    }

    /** Insert an ID without rewriting other metadata or body. */
    public static function withId(string $raw, string $id): string
    {
        if (!self::valid($id)) throw new \InvalidArgumentException('Ungültige Seiten-ID.');
        if (preg_match('/\A---(\R)(.*?)\R---(\R|\z)/s', $raw, $m)) {
            try { $meta = Yaml::parse($m[2]); }
            catch (\Throwable $e) { throw new \RuntimeException('Ungültiges Front Matter.', 0, $e); }
            if (!is_array($meta)) throw new \RuntimeException('Ungültiges Front Matter.');
            if (array_key_exists('id', $meta)) throw new \RuntimeException('Seite hat bereits eine ID.');
            return '---' . $m[1] . 'id: ' . $id . $m[1] . substr($raw, strlen('---' . $m[1]));
        }
        if (str_starts_with($raw, '---')) throw new \RuntimeException('Nicht interpretierbares Front Matter.');
        return "---\nid: " . $id . "\n---\n\n" . $raw;
    }
}
