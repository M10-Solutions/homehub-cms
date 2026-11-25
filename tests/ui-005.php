<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/';
$admin=file_get_contents($root.'admin.php');
$wiki=file_get_contents($root.'index.php');
$css=file_get_contents($root.'css/admin-unified.css');
$account=file_get_contents($root.'sidebar-account-view.php');
foreach (['hub-brand-mark','hub-brand-copy','sidebar-account-view.php','sidebar-account.css?v=3'] as $token) {
    if (!str_contains($admin,$token) || !str_contains($wiki,$token)) throw new RuntimeException('Missing shared component: '.$token);
}
foreach (['hub-sidebar-account-icon','hub-sidebar-account-name','fa-shield-halved','fa-pen-to-square'] as $token) {
    if (!str_contains($account,$token)) throw new RuntimeException('Missing account component: '.$token);
}
if (substr_count($admin,'<span>Zum Wiki</span>')!==1 || !preg_match('~<div class="hub-header-actions">\s*<a[^>]+href="/"[^>]*>.*?<span>Zum Wiki</span>.*?</header>~s',$admin)) throw new RuntimeException('Wiki button missing from header');
if (!preg_match('~<aside class="admin-sidebar".*?sidebar-account-view\.php.*?</aside>~s',$admin) || str_contains($css,'.admin-top .hub-brand-copy small{color:')) throw new RuntimeException('Admin chrome inconsistent');
echo "UI-005 shared chrome checks passed\n";
