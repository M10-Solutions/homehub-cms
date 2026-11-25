<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);

// Development router for PHP's built-in web server.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = dirname(__DIR__) . '/src' . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
require dirname(__DIR__) . '/src/index.php';
