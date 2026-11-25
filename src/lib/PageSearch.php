<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

/** Stateless search over the caller's permission-filtered published pages. */
final class PageSearch
{
    public const MAX_QUERY = 160;

    public static function query(mixed $value): string
    {
        if (!is_string($value) || !preg_match('//u', $value)) return '';
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        preg_match('/^.{0,' . self::MAX_QUERY . '}/us', $value, $match);
        return trim($match[0] ?? '');
    }

    /** @param list<Page> $readablePages Only pages approved by PermissionService. */
    public static function find(array $readablePages, string $query): array
    {
        $query = self::query($query);
        if ($query === '') return [];
        $patterns = array_map(static fn(string $term): string => '~' . preg_quote($term, '~') . '~iu', explode(' ', $query));
        $results = [];
        foreach ($readablePages as $page) {
            if (!$page->published) continue;
            $body = self::plain($page->content);
            $score = 0;
            $matchedTags = [];
            $snippetPattern = null;
            foreach ($patterns as $pattern) {
                $title = preg_match($pattern, $page->title) === 1;
                $description = preg_match($pattern, $page->description) === 1;
                $content = preg_match($pattern, $body) === 1;
                $tags = array_values(array_filter($page->labels, static fn(string $tag): bool => !TagRepository::isId($tag) && preg_match($pattern, $tag) === 1));
                if (!$title && !$description && !$content && !$tags) continue 2;
                $score += ($title ? 8 : 0) + ($tags ? 5 : 0) + ($description ? 3 : 0) + ($content ? 1 : 0);
                $matchedTags = array_merge($matchedTags, $tags);
                if ($content && $snippetPattern === null) $snippetPattern = $pattern;
            }
            $excerpt = $snippetPattern !== null ? self::excerpt($body, $snippetPattern) : self::excerpt(self::plain($page->description) ?: $body, null);
            $results[] = ['page' => $page, 'score' => $score, 'excerpt' => $excerpt, 'tags' => array_values(array_unique($matchedTags))];
        }
        usort($results, static fn(array $a, array $b): int => ($b['score'] <=> $a['score']) ?: strnatcasecmp($a['page']->title, $b['page']->title) ?: strcmp($a['page']->slug, $b['page']->slug));
        return $results;
    }

    private static function plain(string $text): string
    {
        // Keep Markdown link/image labels, not their internal target IDs or URLs.
        $text = preg_replace('~!?\[([^\]\n]*)\]\([^\n)]*\)~u', '$1', $text) ?? $text;
        return trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    }

    private static function excerpt(string $text, ?string $pattern): string
    {
        $offset = 0;
        if ($pattern !== null && preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)) $offset = $match[0][1];
        // Regex offsets are byte offsets. Find a valid UTF-8 boundary before slicing.
        $start = max(0, $offset - 80);
        while ($start < $offset && (ord($text[$start]) & 0xc0) === 0x80) ++$start;
        preg_match('/^.{0,240}/us', substr($text, $start), $match);
        $snippet = $match[0] ?? '';
        return ($start > 0 ? '… ' : '') . trim($snippet) . ($start + strlen($snippet) < strlen($text) ? ' …' : '');
    }
}
