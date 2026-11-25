<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

/** Keeps CMS editing fields separate from the portable Markdown document. */
final class DocumentBody
{
    public static function compose(string $title, string $description, string $body): string
    {
        $heading = '# ' . str_replace(["\r", "\n"], ' ', trim($title));
        $description = trim(preg_replace('/\s*\R\s*/u', ' ', $description) ?? $description);
        $parts = [$heading];
        if ($description !== '') $parts[] = $description;
        if (trim($body) !== '') $parts[] = ltrim($body, "\r\n");
        return implode("\n\n", $parts) . "\n";
    }

    /**
     * Remove only the generated document header matching front matter.
     * Legacy pages without that exact header are left untouched.
     */
    public static function extractContent(string $markdown, string $title, string $description): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $markdown);
        $normalized = ltrim($normalized, "\n");
        $heading = '# ' . str_replace(["\r", "\n"], ' ', trim($title));
        if (!str_starts_with($normalized, $heading . "\n")) return $markdown;
        $tail = substr($normalized, strlen($heading));
        if (!preg_match('/\A\n{2,}/', $tail, $m)) {
            return trim($tail) === '' && trim($description) === '' ? '' : $markdown;
        }
        $tail = substr($tail, strlen($m[0]));
        $description = trim(preg_replace('/\s*\R\s*/u', ' ', $description) ?? $description);
        if ($description !== '') {
            // The description must match exactly and end at a paragraph boundary.
            if (!str_starts_with($tail, $description)) return $markdown;
            $tail = substr($tail, strlen($description));
            // A page without content ends after the description and a newline.
            if (trim($tail) === '') return '';
            if (!preg_match('/\A\n{2,}/', $tail, $m)) return $markdown;
            $tail = substr($tail, strlen($m[0]));
        }
        return $tail;
    }
}
