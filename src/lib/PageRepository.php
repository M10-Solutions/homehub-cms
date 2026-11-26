<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

use League\CommonMark\MarkdownConverter;

final class PageRepository
{
    private MarkdownConverter $markdown;
    private TagRepository $tags;

    public function __construct(private readonly string $contentPath)
    {
        $this->tags = new TagRepository(dirname($this->contentPath) . '/config/tags.json');
        $this->markdown = AlignedMarkdown::converter();
    }

    /** @return list<Page> */
    public function all(): array
    {
        return array_values(array_filter($this->allIncludingDrafts(), static fn (Page $page): bool => $page->published));
    }

    /** Administrative inventory only. Callers must enforce admin permissions. */
    public function allIncludingDrafts(): array
    {
        $pages = [];

        if (!is_dir($this->contentPath)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->contentPath,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($this->contentPath) + 1);
            $slug = strtolower(str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                substr($relative, 0, -3)
            ));

            $page = $this->fromFile($file->getPathname(), $slug);
            if ($page !== null) {
                $pages[] = $page;
            }
        }

        usort($pages, static fn (Page $a, Page $b): int =>
            [$a->order, $a->title] <=> [$b->order, $b->title]
        );

        return $pages;
    }

    public function find(string $slug): ?Page
    {
        // Only allow safe, relative URL slugs.
        if ($slug === '' || str_contains($slug, '..') ||
            !preg_match('~^[a-z0-9/_-]+$~', $slug)) {
            return null;
        }

        $path = $this->contentPath . '/' . $slug . '.md';
        if (!is_file($path)) {
            return null;
        }

        $realContent = realpath($this->contentPath);
        $realFile = realpath($path);

        if ($realContent === false || $realFile === false ||
            !str_starts_with($realFile, $realContent . DIRECTORY_SEPARATOR)) {
            return null;
        }

        $page = $this->fromFile($realFile, $slug);
        return ($page !== null && $page->published) ? $page : null;
    }

    /** Resolve a stable page ID to a published page; fail closed on duplicates. */
    public function findById(string $id): ?Page
    {
        if (!PageIdentity::valid($id)) return null;
        $inventory = PageIdentity::inventory($this->contentPath);
        if (!isset($inventory[$id])) return null;
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $inventory[$id]);
        if (!str_ends_with(strtolower($relative), '.md')) return null;
        $slug = strtolower(substr($relative, 0, -3));
        $page = $this->find($slug);
        return $page !== null && $page->id === $id ? $page : null;
    }

    /** Read an ancestor's policy even when its page is unpublished. */
    public function findForAccess(string $slug): ?Page
    {
        if ($slug === '' || str_contains($slug, '..') ||
            !preg_match('~^[a-z0-9/_-]+$~', $slug)) return null;
        $root = realpath($this->contentPath);
        $path = realpath($this->contentPath . '/' . $slug . '.md');
        if ($root === false || $path === false ||
            !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            return file_exists($this->contentPath . '/' . $slug . '.md') || is_link($this->contentPath . '/' . $slug . '.md')
                ? self::invalidPage($slug) : null;
        }
        return $this->fromFile($path, $slug);
    }

    public function findByIdForAccess(string $id): ?Page
    {
        if (!PageIdentity::valid($id)) return null;
        $relative = PageIdentity::inventory($this->contentPath)[$id] ?? null;
        if (!is_string($relative)) return null;
        $page = $this->findForAccess(strtolower(substr($relative, 0, -3)));
        return $page !== null && $page->id === $id ? $page : null;
    }

    public function renderRaw(Page $page): string
    {
        return AlignedMarkdown::render($page->content, $this->markdown);
    }

    public function render(Page $page): string
    {
        $html = $this->renderRaw($page);
        return MediaAccess::contextualize($html, ($page->id === null ? [] : ['page'=>$page->id]) + ['slug'=>$page->slug]);
    }

    private function fromFile(string $path, string $slug): ?Page
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            return self::invalidPage($slug);
        }

        try {
            [$meta, $body] = PageMetadata::parse($raw);
        } catch (\Throwable) {
            return self::invalidPage($slug);
        }

        $parts = explode('/', $slug);
        $defaultCategory = count($parts) > 1 ? $parts[0] : '';

        return new Page(
            slug: $slug,
            id: PageIdentity::valid($meta['id'] ?? null) ? $meta['id'] : null,
            title: (string) ($meta['title'] ?? ucfirst(str_replace('-', ' ', basename($slug)))),
            description: (string) ($meta['description'] ?? ''),
            content: DocumentBody::extractContent($body, (string) ($meta['title'] ?? ucfirst(str_replace('-', ' ', basename($slug)))), (string) ($meta['description'] ?? '')),
            category: $defaultCategory,
            order: (int) ($meta['order'] ?? 100),
            showInNavigation: (bool) ($meta['nav'] ?? true),
            published: (bool) ($meta['published'] ?? true),
            labels: $this->tags->names(self::parseLabels($meta['labels'] ?? [])),
            visibility: in_array(($meta['visibility'] ?? 'all'), ['all', 'authenticated', 'roles', 'users'], true) ? $meta['visibility'] ?? 'all' : 'roles',
            allowedRoles: self::parseRoles($meta['allowed_roles'] ?? []),
            allowedUsers: self::parseUserIds($meta['allowed_users'] ?? []),
            dateCreated: self::parseDate($meta['date-created'] ?? null),
            dateModified: self::parseDate($meta['date-modified'] ?? null) ?? date(DATE_ATOM, (int) (filemtime($path) ?: time())),
        );
    }
    private static function invalidPage(string $slug): Page
    {
        return new Page($slug, $slug, '', '', '', 100, false, false,
            visibility: 'roles', metadataValid: false);
    }

    private static function parseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) return $value->format(DATE_ATOM);
        if (!is_string($value) || trim($value) === '') return null;
        try { return (new \DateTimeImmutable($value))->format(DATE_ATOM); }
        catch (\Throwable) { return null; }
    }

    private static function parseUserIds(mixed $ids): array
    {
        if (!is_array($ids)) return [];
        return array_values(array_unique(array_filter($ids, static fn($id) => is_string($id) && (bool) preg_match('/\Au_[a-f0-9]{32}\z/D', $id))));
    }

    private static function parseRoles(mixed $roles): array
    {
        if (!is_array($roles)) return [];
        return array_values(array_unique(array_filter(array_map(static fn($r) => match ($r) { 'member' => 'editor', 'visitor' => 'reader', default => $r }, $roles), static fn($r) => is_string($r) && in_array($r, ['reader', 'editor', 'admin'], true))));
    }

    /** @return list<string> */
    private static function parseLabels(mixed $value): array
    {
        if (is_string($value)) $value = explode(',', $value);
        if (!is_array($value)) return [];
        $result = [];
        foreach ($value as $label) {
            if (!is_string($label)) continue;
            $label = trim($label);
            if ($label === '' || mb_strlen($label) > 40 || in_array($label, $result, true)) continue;
            $result[] = $label;
            if (count($result) >= 20) break;
        }
        return $result;
    }

}
