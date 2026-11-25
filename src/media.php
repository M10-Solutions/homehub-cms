<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; sandbox');
try {
    $media = new \HomeHub\MediaLibrary(dirname(__DIR__) . '/data/upload');
    $name = (string) ($_GET['file'] ?? '');
    if (isset($_GET['id'])) {
        $name = (new \HomeHub\MediaRegistry(dirname(__DIR__) . '/data/config'))->pathForId((string) $_GET['id']);
    }
    $file = $media->resolve($name);
    $mime = $file['mime'];
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file['path']));
    header('Cache-Control: private, max-age=3600');
    if (!str_starts_with($mime, 'image/')) {
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
    }
    readfile($file['path']);
} catch (\Throwable) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Datei nicht gefunden.';
}
