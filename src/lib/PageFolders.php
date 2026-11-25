<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

/** Read-only folder discovery for the page move dialog. */
final class PageFolders
{
    /** @return list<string> Root folder is represented by an empty string. */
    public static function all(string $contentDir): array
    {
        $root = realpath($contentDir);
        if ($root === false || !is_dir($root)) return [''];
        $folders = [''];
        $walk = static function (string $path, string $prefix) use (&$walk, &$folders): void {
            $entries = scandir($path);
            if ($entries === false) return;
            foreach ($entries as $name) {
                if ($name === '.' || $name === '..') continue;
                $absolute = $path . DIRECTORY_SEPARATOR . $name;
                if (is_link($absolute) || !is_dir($absolute)) continue;
                $relative = $prefix === '' ? $name : $prefix . '/' . $name;
                if (!Admin::slugIsValid($relative)) continue;
                $folders[] = $relative;
                $walk($absolute, $relative);
            }
        };
        $walk($root, '');
        sort($folders, SORT_STRING);
        return $folders;
    }
}
