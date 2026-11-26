<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\DocumentExporter;
use HomeHub\Page;
use HomeHub\PageRepository;
use HomeHub\PermissionService;
use HomeHub\JSONAuthenticator;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$slug = (string) ($_GET['page'] ?? '');
$format = (string) ($_GET['format'] ?? '');
$scope = (string) ($_GET['scope'] ?? 'tree');
if (!preg_match('~\A[a-z0-9_-]+(?:/[a-z0-9_-]+)*\z~D', $slug)
    || !in_array($format, ['pdf', 'odt', 'zip'], true)
    || !in_array($scope, ['page', 'tree'], true)) {
    http_response_code(400);
    exit('Ungültige Exportanfrage.');
}

$config = require __DIR__ . '/config/app.php';
$repository = new PageRepository($config['content_path']);
$root = $repository->find($slug);
if ($root === null) {
    http_response_code(404);
    exit('Seite nicht gefunden.');
}
Session::start();
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
$role = $auth->currentRole();
$userId = $auth->currentUserId();
if (!PermissionService::canReadPage($root, $role, $repository, $userId)) {
    http_response_code($auth->isSigned() ? 403 : 401);
    exit('Kein Zugriff auf diese Seite.');
}

// Build a depth-first outline in navigation order, including hidden-from-nav
// descendants if they are published and readable. Never export denied pages.
$pages = [$root];
if ($scope === 'tree') {
    $descendants = [];
    foreach ($repository->all() as $page) {
        if (str_starts_with($page->slug, $slug . '/')
            && PermissionService::canReadPage($page, $role, $repository, $userId)) {
            $descendants[$page->slug] = $page;
        }
    }
    // Sort by ancestor chain, with sibling order taken from page metadata.
    $pages = array_merge([$root], array_values($descendants));
    $lookup = [$slug => $root] + $descendants;
    usort($pages, static function (Page $a, Page $b) use ($slug, $lookup): int {
        if ($a->slug === $slug) return -1;
        if ($b->slug === $slug) return 1;
        $pa = explode('/', substr($a->slug, strlen($slug) + 1));
        $pb = explode('/', substr($b->slug, strlen($slug) + 1));
        $prefix = $slug;
        for ($i = 0, $len = min(count($pa), count($pb)); $i < $len; $i++) {
            if ($pa[$i] !== $pb[$i]) {
                $aa = $lookup[$prefix . '/' . $pa[$i]] ?? null;
                $bb = $lookup[$prefix . '/' . $pb[$i]] ?? null;
                return [($aa?->order ?? 100), mb_strtolower($aa?->title ?? $pa[$i]), $pa[$i]]
                    <=> [($bb?->order ?? 100), mb_strtolower($bb?->title ?? $pb[$i]), $pb[$i]];
            }
            $prefix .= '/' . $pa[$i];
        }
        return count($pa) <=> count($pb);
    });
}

if ($format === 'zip') {
    try {
        $result = (new \HomeHub\ChapterZipExporter($config['content_path'], dirname(__DIR__) . '/data/upload', dirname(__DIR__) . '/data/config'))->export($pages, $slug);
        try {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . filesize($result['path']));
            readfile($result['path']);
        } finally { DocumentExporter::cleanup($result['directory']); }
    } catch (\Throwable $exception) {
        error_log('HomeHub ZIP-Export: ' . $exception->getMessage());
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Der ZIP-Export ist fehlgeschlagen. Bitte Server-Log prüfen.';
    }
    exit;
}

$options = [
    'collection' => true,
    'pages' => $pages,
    'cover' => ($_GET['cover'] ?? '') === '1',
    'toc' => ($_GET['toc'] ?? '') === '1',
    'numbering' => ($_GET['numbering'] ?? '') === '1',
];
try {
    $result = (new DocumentExporter(dirname(__DIR__) . '/data/upload'))->export($root, $format, $options);
    try {
        header('Content-Type: ' . $result['mime']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . filesize($result['path']));
        readfile($result['path']);
    } finally {
        DocumentExporter::cleanup($result['directory']);
    }
} catch (\Throwable $exception) {
    error_log('HomeHub Kapitelexport: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Der Kapitelexport ist fehlgeschlagen. Bitte Server-Log prüfen.';
}
