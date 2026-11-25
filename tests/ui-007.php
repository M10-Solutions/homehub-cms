<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root = dirname(__DIR__).'/src/';
$admin = file_get_contents($root.'admin.php');
$css = file_get_contents($root.'css/admin-navigation.css');
foreach (['data-admin-nav-toggle','admin-workspace-toolbar','/js/admin-navigation.js'] as $token) {
    if (str_contains($admin,$token)) throw new RuntimeException('Obsolete admin navigation: '.$token);
}
if (str_contains($css,'nav-collapsed') || str_contains($css,'admin-nav-toggle')) throw new RuntimeException('Obsolete collapse styles');
foreach (['Medienverwaltung','Benutzerverwaltung','sidebar-account-view.php'] as $token) {
    if (!str_contains($admin,$token)) throw new RuntimeException('Missing admin navigation: '.$token);
}
if (!str_contains(file_get_contents($root.'index.php'),'id="hub-nav-toggle"')) throw new RuntimeException('Wiki navigation toggle missing');
echo "UI-007 admin navigation checks passed\n";
