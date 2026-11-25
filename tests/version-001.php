<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root = dirname(__DIR__) . '/src';
$config = require $root . '/config/app.php';
$version = $config['version'] ?? null;
if (!is_string($version) || !preg_match('/\A\d+\.\d+-(\d{4})(\d{2})(\d{2})\z/', $version, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
    throw new RuntimeException('Invalid configured release version');
}
$wiki = file_get_contents($root . '/index.php');
if (!preg_match('~<footer class="footer hub-public-footer">(.*?)</footer>~s', $wiki, $footer) || !str_contains($footer[1], 'class="hub-app-version">Version <?= e($config[\'version\']) ?>')) {
    throw new RuntimeException('Missing escaped configured version in wiki footer');
}
$css = file_get_contents($root . '/css/login-modal.css');
if (!preg_match('/\.hub-public-footer \.hub-app-version\s*\{[^}]*margin-left:\s*auto;/', $css)) throw new RuntimeException('Missing right alignment');
echo "Configured version and wiki footer checks passed\n";
