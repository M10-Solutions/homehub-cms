<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\PageIdentity;
use HomeHub\PageRepository;
use HomeHub\PermissionService;
use HomeHub\JSONAuthenticator;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/config/app.php';
Session::start();
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex');
$id = (string) ($_GET['id'] ?? '');
if (!PageIdentity::valid($id)) { http_response_code(404); exit('Seite nicht gefunden.'); }
try {
    $pages = new PageRepository($config['content_path']);
    $page = $pages->findById($id);
    if ($page === null) { http_response_code(404); exit('Seite nicht gefunden.'); }
    $auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
    if (!PermissionService::canReadPage($page, $auth->currentRole(), $pages, $auth->currentUserId())) {
        http_response_code($auth->currentRole() === null ? 401 : 403);
        exit('Kein Zugriff.');
    }
    header('Location: /' . implode('/', array_map('rawurlencode', explode('/', $page->slug))), true, 302);
} catch (\Throwable $e) {
    error_log('Page ID resolution failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Seitenreferenz derzeit nicht verfügbar.');
}
