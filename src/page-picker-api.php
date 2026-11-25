<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\JSONAuthenticator;
use HomeHub\PageRepository;
use HomeHub\PermissionService;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/config/app.php';
Session::start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); exit('{}'); }
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
$role = $auth->currentRole();
if (!PermissionService::can($role, 'page.edit')) { http_response_code(403); exit('{"error":"Keine Berechtigung"}'); }
try {
    $repo = new PageRepository($config['content_path']);
    $items = [];
    foreach ($repo->all() as $page) {
        if ($page->id === null || !PermissionService::canReadPage($page, $role, $repo, $auth->currentUserId())) continue;
        $items[] = ['id' => $page->id, 'title' => $page->title, 'slug' => $page->slug];
    }
    usort($items, static fn(array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));
    echo json_encode(['pages' => $items], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('Page picker failed: ' . $e->getMessage());
    http_response_code(503);
    echo '{"error":"Seitenliste derzeit nicht verfügbar"}';
}
