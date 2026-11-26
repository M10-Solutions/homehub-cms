<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; sandbox');
try {
    \HomeHub\Session::start();
    header('Cache-Control: private, no-store');
    $config = require __DIR__ . '/config/app.php';
    $auth = new \HomeHub\JSONAuthenticator(dirname(__DIR__) . '/data/config');
    $role = $auth->currentRole();
    $userId = $auth->currentUserId();
    session_write_close();
    if ($auth->needsSetup() || !\HomeHub\MediaAccess::allowed($_GET, $role, $userId, new \HomeHub\PageRepository($config['content_path']))) {
        throw new \RuntimeException('Nicht verfügbar.');
    }
    $media = new \HomeHub\MediaLibrary(dirname(__DIR__) . '/data/upload');
    $name = (string) ($_GET['file'] ?? '');
    if (isset($_GET['id'])) {
        $name = (new \HomeHub\MediaRegistry(dirname(__DIR__) . '/data/config'))->pathForId((string) $_GET['id']);
    }
    $file = $media->resolve($name);
    $mime = $file['mime'];
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file['path']));
    if (!str_starts_with($mime, 'image/')) {
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
    }
    readfile($file['path']);
} catch (\Throwable) {
    header('Cache-Control: private, no-store');
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Datei nicht gefunden.';
}
