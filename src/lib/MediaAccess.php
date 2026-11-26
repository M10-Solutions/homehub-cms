<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
namespace HomeHub;

/** Shared URL semantics for rendered references and the delivery endpoint. */
final class MediaAccess
{
    public static function selector(array $query): ?array
    {
        if (array_key_exists('id', $query) === array_key_exists('file', $query)) return null;
        if (isset($query['id']) && is_string($query['id']) && preg_match('/\A[a-f0-9]{32}\z/D', $query['id'])) return ['id'=>$query['id']];
        if (isset($query['file']) && is_string($query['file']) && MediaLibrary::validRelativePath($query['file'])) return ['file'=>$query['file']];
        return null;
    }

    public static function reference(string $url): ?array
    {
        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['path'] ?? '') !== '/media.php' || isset($parts['scheme']) || isset($parts['host'])) return null;
        parse_str($parts['query'] ?? '', $query);
        if (count($query) !== 1) return null;
        return self::selector($query);
    }

    /** Inspect only actual rendered links/images, never code or plain text. */
    private static function visit(string $html, callable $visitor, bool $rewrite): string
    {
        if (!str_contains($html, 'media.php')) return $html;
        $dom = new \DOMDocument();
        if (!$dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new \RuntimeException('Medienreferenzen konnten nicht gelesen werden.');
        foreach (['a'=>'href', 'img'=>'src'] as $tag=>$attr) {
            foreach ($dom->getElementsByTagName($tag) as $node) {
                $url = $node->getAttribute($attr);
                $selector = self::reference($url);
                if ($selector === null) continue;
                $replacement = $visitor($selector);
                if ($rewrite && is_string($replacement)) $node->setAttribute($attr, $replacement);
            }
        }
        if (!$rewrite) return $html;
        $body = $dom->getElementsByTagName('body')->item(0);
        $out = '';
        foreach ($body->childNodes as $node) $out .= $dom->saveHTML($node);
        return $out;
    }

    public static function contextualize(string $html, array $context): string
    {
        return self::visit($html, static fn(array $selector): string => '/media.php?' . http_build_query($selector + $context, '', '&', PHP_QUERY_RFC3986), true);
    }

    public static function references(string $html): array
    {
        $references = [];
        self::visit($html, static function(array $selector) use (&$references): void {
            $references[http_build_query($selector)] = $selector;
        }, false);
        return array_values($references);
    }

    public static function contains(string $html, array $selector): bool
    {
        $found = false;
        self::visit($html, static function(array $candidate) use (&$found, $selector): void { if ($candidate === $selector) $found = true; }, false);
        return $found;
    }

    /** Only managed raster images may reach the document converter. */
    public static function exportImages(string $html, MediaLibrary $library, MediaRegistry $registry): string
    {
        $dom = new \DOMDocument();
        if (!$dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new \RuntimeException('Export-HTML ungültig.');
        foreach (iterator_to_array($dom->getElementsByTagName('img')) as $image) {
            try {
                $selector = self::reference($image->getAttribute('src'));
                if ($selector === null) throw new \RuntimeException('Ungültige Bildreferenz.');
                $name = isset($selector['id']) ? $registry->pathForId($selector['id']) : $selector['file'];
                $file = $library->resolve($name);
                if (!str_starts_with($file['mime'], 'image/')) throw new \RuntimeException('Kein Bild.');
                $image->setAttribute('src', $file['path']);
            } catch (\Throwable) {
                $image->parentNode->replaceChild($dom->createTextNode($image->getAttribute('alt')), $image);
            }
        }
        $out = '';
        foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) $out .= $dom->saveHTML($node);
        return $out;
    }

    public static function allowed(array $query, ?string $role, ?string $userId, PageRepository $repo): bool
    {
        $selector = self::selector($query);
        if ($selector === null) return false;
        if (($query['context'] ?? null) === 'library') {
            return !array_key_exists('page', $query) && !array_key_exists('slug', $query) && PermissionService::can($role, 'media.read');
        }
        if (array_key_exists('context', $query)) return false;
        $id = $query['page'] ?? null;
        $slug = $query['slug'] ?? null;
        if ($id !== null && (!is_string($id) || !PageIdentity::valid($id))) return false;
        $page = is_string($slug) && preg_match('~\A[a-z0-9_-]+(?:/[a-z0-9_-]+)*\z~D', $slug) ? $repo->findForAccess($slug) : null;
        if ($id !== null && ($page === null || $page->id !== $id)) $page = $repo->findByIdForAccess($id);
        if ($page === null || $page->id !== $id) return false;
        if (!($page->published ? PermissionService::canReadPage($page, $role, $repo, $userId) : PermissionService::canReadDraft($page, $role, $repo, $userId))) return false;
        return self::contains($repo->renderRaw($page), $selector);
    }
}
