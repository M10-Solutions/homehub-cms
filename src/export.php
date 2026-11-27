<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\DocumentExporter;
use HomeHub\PageRepository;
use HomeHub\PermissionService;
use HomeHub\JSONAuthenticator;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$format = (string) ($_GET['format'] ?? '');
$slug = (string) ($_GET['page'] ?? '');
if (!in_array($format, ['pdf', 'odt'], true) || !preg_match('~\A[a-z0-9/_-]+\z~D', $slug) || str_contains($slug, '..')) {
    http_response_code(400);
    exit('Ungültige Exportanfrage.');
}

Session::start();
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
if ($format !== 'pdf' && !$auth->isSigned()) {
    http_response_code(401);
    exit('Anmeldung erforderlich.');
}

$config = require __DIR__ . '/config/app.php';
$repository = new PageRepository($config['content_path']);
$page = $repository->find($slug);
if ($page === null) {
    http_response_code(404);
    exit('Seite nicht gefunden.');
}
if (!PermissionService::canReadPage($page, $auth->currentRole(), $repository, $auth->currentUserId())) {
    http_response_code($auth->isSigned() ? 403 : 401);
    exit('Kein Zugriff auf diese Seite.');
}

try {
    $result = (new DocumentExporter(dirname(__DIR__) . '/data/upload'))->export($page, $format);
    try {
        header('Content-Type: ' . $result['mime']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . filesize($result['path']));
        readfile($result['path']);
    } finally {
        DocumentExporter::cleanup($result['directory']);
    }
} catch (\Throwable $exception) {
    error_log('HomeHub Dokumentexport: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Der Dokumentexport ist fehlgeschlagen. Bitte Server-Log prüfen.';
}
