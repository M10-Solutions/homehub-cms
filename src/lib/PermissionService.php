<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

namespace HomeHub;

/** Central server-side permissions for the HomeHub role model. */
final class PermissionService
{
    private const PERMISSIONS = [
        'reader' => ['page.read'],
        'editor' => ['page.read', 'page.read_draft', 'page.edit', 'page.create', 'page.delete', 'page.move', 'page.publish', 'media.read', 'media.upload'],
        'admin' => [
            'page.read', 'page.read_draft', 'page.edit', 'page.create',
            'page.delete', 'page.move', 'page.publish', 'user.manage',
            'media.manage', 'media.read', 'media.upload',
        ],
    ];

    public static function canReadPage(Page $page, ?string $role, ?PageRepository $repository = null, ?string $userId = null): bool
    {
        if (!$page->metadataValid || !$page->published) return false;
        // Admins can read all published pages, regardless of inherited restrictions.
        if ($role === 'admin') return true;
        if (!self::matchesVisibility($page, $role, $userId)) return false;
        if ($repository === null) {
            // Do not silently bypass ancestor restrictions when no repository is given.
            return !str_contains($page->slug, '/');
        }
        foreach (self::ancestorSlugs($page->slug) as $ancestorSlug) {
            $ancestor = $repository->findForAccess($ancestorSlug);
            // Directories without a Markdown page have no access policy.
            if ($ancestor !== null && !self::matchesVisibility($ancestor, $role, $userId)) return false;
        }
        return true;
    }

    /** Draft access is limited to admins and editors with the page's existing ACL,
     * including restrictions inherited from ancestor pages. Never used by exports/search. */
    public static function canReadDraft(Page $page, ?string $role, ?PageRepository $repository = null, ?string $userId = null): bool
    {
        if (!$page->metadataValid || $page->published) return false;
        if (!self::can($role, 'page.read_draft')) return false;
        if ($role === 'admin') return true;
        if (!self::matchesVisibility($page, $role, $userId) || $repository === null) return false;
        foreach (self::ancestorSlugs($page->slug) as $ancestorSlug) {
            $ancestor = $repository->findForAccess($ancestorSlug);
            if ($ancestor !== null && !self::matchesVisibility($ancestor, $role, $userId)) return false;
        }
        return true;
    }

    /** Editor mutations require the same effective ACL as reading the page. */
    public static function canManagePage(Page $page, ?string $role, PageRepository $repository, ?string $userId): bool
    {
        if (!$page->metadataValid) return false;
        if ($role === 'admin') return true;
        if ($role !== 'editor') return false;
        return $page->published
            ? self::canReadPage($page, $role, $repository, $userId)
            : self::canReadDraft($page, $role, $repository, $userId);
    }

    /** A new editor page must be placed beneath an existing, accessible chapter page.
     * This avoids creating unrestricted root pages or bypassing inherited ACLs. */
    public static function canCreateAt(string $slug, ?string $role, PageRepository $repository, ?string $userId): bool
    {
        if ($role === 'admin') return true;
        if ($role !== 'editor') return false;
        $ancestors = self::ancestorSlugs($slug);
        if ($ancestors === []) return false;
        $chapterFound = false;
        foreach ($ancestors as $ancestorSlug) {
            $ancestor = $repository->findForAccess($ancestorSlug);
            if ($ancestor === null) continue;
            $chapterFound = true;
            if (!self::canManagePage($ancestor, $role, $repository, $userId)) return false;
        }
        return $chapterFound;
    }

    private static function matchesVisibility(Page $page, ?string $role, ?string $userId): bool
    {
        if (!$page->metadataValid) return false;
        return match ($page->visibility) {
            'all' => true,
            'authenticated' => $role !== null,
            'roles' => $role !== null && in_array($role, $page->allowedRoles, true),
            'users' => $userId !== null && in_array($userId, $page->allowedUsers, true),
            default => false,
        };
    }

    /** @return list<string> */
    public static function ancestorSlugs(string $slug): array
    {
        $parts = explode('/', $slug);
        array_pop($parts);
        $result = [];
        $prefix = '';
        foreach ($parts as $part) {
            $prefix = $prefix === '' ? $part : $prefix . '/' . $part;
            $result[] = $prefix;
        }
        return $result;
    }

    /** Human-readable inherited restrictions for the administrator UI. */
    public static function inheritedPolicies(string $slug, PageRepository $repository): array
    {
        $result = [];
        foreach (self::ancestorSlugs($slug) as $ancestorSlug) {
            $page = $repository->findForAccess($ancestorSlug);
            if ($page !== null && $page->visibility !== 'all') {
                $result[] = ['slug' => $ancestorSlug, 'visibility' => $page->visibility,
                    'roles' => $page->allowedRoles, 'users' => $page->allowedUsers];
            }
        }
        return $result;
    }

    public static function can(?string $role, string $permission): bool
    {
        return in_array($permission, self::PERMISSIONS[$role ?? ''] ?? [], true);
    }
}
