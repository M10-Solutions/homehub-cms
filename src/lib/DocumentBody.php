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
        $header = $heading . ($description !== '' ? "\n\n" . $description : '');
        return $body === '' ? $header . "\n" : $header . "\n\n" . $body;
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
        $description = trim(preg_replace('/\s*\R\s*/u', ' ', $description) ?? $description);
        $header = $heading . ($description !== '' ? "\n\n" . $description : '');
        if ($normalized === $header || $normalized === $header . "\n") return '';
        $prefix = $header . "\n\n";
        if (!str_starts_with($normalized, $prefix)) return $markdown;
        return substr($normalized, strlen($prefix));
    }
}
