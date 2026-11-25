<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

/** A single tree for the public site and the administration.
 * Root Markdown pages are uncategorized. The first directory is the category.
 * A node may simultaneously represent a Markdown page and a folder.
 */
final class NavigationTree
{
    /** @param iterable<array{slug:string,title:string,published?:bool}> $pages */
    public static function build(iterable $pages): array
    {
        $tree = ['segment' => '', 'slug' => '', 'page' => null, 'children' => [], 'order' => 100];
        foreach ($pages as $page) {
            $slug = (string) $page['slug'];
            if (!Admin::slugIsValid($slug)) continue;
            $node =& $tree;
            $path = '';
            foreach (explode('/', $slug) as $segment) {
                $path = $path === '' ? $segment : $path . '/' . $segment;
                if (!isset($node['children'][$segment])) {
                    $node['children'][$segment] = ['segment' => $segment, 'slug' => $path, 'page' => null, 'children' => [], 'order' => 100];
                }
                $node =& $node['children'][$segment];
            }
            $node['page'] = $page;
            $node['order'] = (int) ($page['order'] ?? 100);
            unset($node);
        }
        self::sort($tree);
        return $tree;
    }

    private static function sort(array &$node): void
    {
        foreach ($node['children'] as &$child) {
            self::sort($child);
            // A folder without its own page inherits the smallest order of its descendants.
            if ($child['page'] === null && $child['children'] !== []) {
                $child['order'] = min(array_column($child['children'], 'order'));
            }
        }
        unset($child);
        uasort($node['children'], static fn (array $a, array $b): int =>
            ($a['order'] <=> $b['order']) ?: strnatcasecmp(
                (string) ($a['page']['title'] ?? $a['segment']),
                (string) ($b['page']['title'] ?? $b['segment'])
            )
        );
    }
}
