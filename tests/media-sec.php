<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use HomeHub\MediaAccess;
use HomeHub\PageRepository;
$id = str_repeat('a',32); $pid = 'p_' . str_repeat('b',32);
$root = sys_get_temp_dir() . '/homehub-medsec-' . bin2hex(random_bytes(8));
mkdir($root); mkdir($root.'/content'); mkdir($root.'/config');
function check(bool $value): void { if (!$value) throw new RuntimeException('MED-SEC assertion failed'); }
try {
    $path = $root.'/content/test.md';
    $write = static function(string $visibility, string $body) use ($path,$pid): void { file_put_contents($path,"---\nid: $pid\nvisibility: $visibility\n---\n$body\n"); };
    $write('authenticated', "![Bild](/media.php?id=$id)");
    $repo = new PageRepository($root.'/content');
    $q = ['id'=>$id,'page'=>$pid,'slug'=>'test'];
    check(!MediaAccess::allowed($q,null,null,$repo));
    check(MediaAccess::allowed($q,'reader',null,$repo));
    check(!MediaAccess::allowed(['id'=>$id],'admin',null,$repo));
    check(!MediaAccess::allowed(['id'=>$id,'context'=>'library'],'reader',null,$repo));
    check(MediaAccess::allowed(['id'=>$id,'context'=>'library'],'editor',null,$repo));
    check(!MediaAccess::allowed(array_replace($q,['id'=>str_repeat('c',32)]),'reader',null,$repo));
    check(str_contains($repo->render($repo->findForAccess('test')), 'page='.$pid));
    $write('all', "`![Bild](/media.php?id=$id)`");
    check(!MediaAccess::allowed($q,null,null,$repo));
    $write('all', "![Bild][ref]\n\n[ref]: /media.php?id=$id");
    check(MediaAccess::allowed($q,null,null,$repo));
    echo "MED-SEC checks passed\n";
} finally { unlink($root.'/content/test.md'); rmdir($root.'/content'); rmdir($root.'/config'); rmdir($root); }
