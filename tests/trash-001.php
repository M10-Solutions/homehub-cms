<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
foreach (['Admin','PageTrash','PageEditLease','MediaOperationLock','PageIdentity'] as $class) require dirname(__DIR__).'/src/lib/'.$class.'.php';
use HomeHub\PageTrash;
use HomeHub\PageEditLease;
$root=sys_get_temp_dir().'/homehub-trash-'.bin2hex(random_bytes(8));
mkdir($root.'/content/a',0770,true); mkdir($root.'/config');
$check=static function($ok){if(!$ok) throw new RuntimeException('Trash assertion failed');};
$reject=static function($fn){try{$fn();}catch(RuntimeException){return;}throw new RuntimeException('Expected rejection');};
try {
    file_put_contents($root.'/content/a.md','root'); file_put_contents($root.'/content/a/b.md','child');
    $trash=new PageTrash($root.'/content',$root.'/config'); $allow=static fn($s)=>null;
    $reject(fn()=> $trash->remove('a',true,'editor',static function($s){if($s==='a/b') throw new RuntimeException('ACL');}));
    $check(is_file($root.'/content/a.md') && is_file($root.'/content/a/b.md'));
    $lease=new PageEditLease($root.'/config'); $token=$lease->acquire('a/b','editor');
    $reject(fn()=> $trash->remove('a',true,'admin',$allow)); $lease->release('a/b','editor',$token);
    $id=$trash->remove('a',false,'admin',$allow); $check(!is_file($root.'/content/a.md') && is_file($root.'/content/a/b.md'));
    $trash->restore($id); $check(file_get_contents($root.'/content/a.md')==='root');
    $id=$trash->remove('a',true,'admin',$allow); $check(count($trash->entries()[$id]['pages'])===2 && !is_file($root.'/content/a/b.md'));
    file_put_contents($root.'/content/a.md','collision'); $reject(fn()=> $trash->restore($id)); unlink($root.'/content/a.md');
    $trash->restore($id); $check(file_get_contents($root.'/content/a/b.md')==='child' && !$trash->entries());
    $id=$trash->remove('a/b',false,'admin',$allow); $trash->purge($id); $check(!$trash->entries());
    $trash->remove('a',false,'admin',$allow); $trash->empty(); $check(!$trash->entries());
    echo "OK: trash lifecycle, ACL rejection, lease and path collision\n";
} finally {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $f) { if($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname()); } rmdir($root);
}
