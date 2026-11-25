<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
namespace HomeHub;
use Symfony\Component\Yaml\Yaml;

/** Shared fail-closed front matter parser. Plain legacy Markdown remains supported. */
final class PageMetadata
{
    public static function parse(string $raw): array
    {
        $raw = preg_replace('/\A\xEF\xBB\xBF/', '', $raw);
        if (!preg_match('/\A[ \t]*---[ \t]*(?:\R|\z)/', $raw)) return [[], $raw];
        if (!preg_match('/\A---\R(.*?)^---(?:\R|\z)(.*)\z/ms', $raw, $m)) {
            throw new \RuntimeException('Ungültiges Front Matter.');
        }
        $meta = Yaml::parse($m[1]);
        if (!is_array($meta) || array_is_list($meta)) throw new \RuntimeException('Ungültiges Front Matter.');
        foreach (['published', 'nav'] as $key) {
            if (array_key_exists($key, $meta) && !is_bool($meta[$key])) throw new \RuntimeException('Ungültiges Metadatenfeld: ' . $key);
        }
        if (array_key_exists('visibility', $meta) && !in_array($meta['visibility'], ['all', 'authenticated', 'roles', 'users'], true)) throw new \RuntimeException('Ungültige Sichtbarkeit.');
        foreach (['allowed_roles', 'allowed_users'] as $key) {
            if (!array_key_exists($key, $meta)) continue;
            if (!is_array($meta[$key]) || !array_is_list($meta[$key])) throw new \RuntimeException('Ungültige ACL.');
            foreach ($meta[$key] as $value) {
                if (!is_string($value) || ($key === 'allowed_roles' ? !in_array($value, ['reader','editor','admin','member','visitor'], true) : !preg_match('/\Au_[a-f0-9]{32}\z/D', $value))) throw new \RuntimeException('Ungültige ACL.');
            }
        }
        if (!array_key_exists('visibility', $meta) && (!empty($meta['allowed_roles']) || !empty($meta['allowed_users']))) throw new \RuntimeException('ACL ohne Sichtbarkeit.');
        foreach (['title','description'] as $key) {
            if (isset($meta[$key]) && !is_scalar($meta[$key])) throw new \RuntimeException('Ungültiges Metadatenfeld: ' . $key);
        }
        return [$meta, $m[2]];
    }
}
