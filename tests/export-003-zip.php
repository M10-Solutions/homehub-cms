<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
foreach (['Page','MediaLibrary','MediaRegistry','MediaOperationLock','DocumentExporter','ChapterZipExporter'] as $class) require dirname(__DIR__).'/src/lib/'.$class.'.php';
use HomeHub\Page;
use HomeHub\ChapterZipExporter;
use HomeHub\MediaRegistry;
$dir = sys_get_temp_dir().'/hh-zip-test-'.bin2hex(random_bytes(8));
mkdir($dir.'/content/root',0700,true); mkdir($dir.'/upload/photos',0700,true); mkdir($dir.'/config',0700,true);
try {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
    file_put_contents($dir.'/upload/photos/a.png',$png);
    $id=(new MediaRegistry($dir.'/config'))->register('photos/a.png');
    $url='/media.php?id='.$id;
    $raw="---\ntitle: Root\n---\n![A]($url)\n[Child](/page.php?id=p_".str_repeat('a',32).")\n```md\n![Code]($url)\n```\n";
    file_put_contents($dir.'/content/root.md',$raw);
    file_put_contents($dir.'/content/root/child.md',"![Legacy](/media.php?file=photos%2Fa.png)\n![Missing](/media.php?file=missing.png)\n");
    file_put_contents($dir.'/content/root/excluded.md','secret');
    $pages=[new Page('root','Root','','','',1,true,true),new Page('root/child','Child','','','',1,true,true,id:'p_'.str_repeat('a',32))];
    $export=new ChapterZipExporter($dir.'/content',$dir.'/upload',$dir.'/config');
    $entries=[];
    $export->collect($pages,static function($n,$s)use(&$entries){$entries[$n]=$s;},static function($n,$p)use(&$entries){$entries[$n]=file_get_contents($p);});
    if(count($entries)!==4 || $entries['media/photos/a.png']!==$png || !str_contains($entries['pages/root.md'],'![A](../media/photos/a.png)') || !str_contains($entries['pages/root.md'],'[Child](../pages/root/child.md)') || !str_contains($entries['pages/root.md'],"![Code]($url)") || !str_contains($entries['pages/root/child.md'],'../../media/photos/a.png') || !str_contains($entries['EXPORT-HINWEISE.txt'],'missing.png') || file_get_contents($dir.'/content/root.md')!==$raw) throw new RuntimeException('ZIP entries incorrect');
    if(class_exists(ZipArchive::class)) {
        $result=$export->export($pages,'root');
        try { $zip=new ZipArchive(); if($zip->open($result['path'])!==true) throw new RuntimeException('ZIP open failed'); foreach($entries as $name=>$value) if($zip->getFromName($name)!==$value) throw new RuntimeException('Archive content mismatch'); $zip->close(); } finally { HomeHub\DocumentExporter::cleanup($result['directory']); }
    } else echo "ZIP transport not available; entry checks only\n";
    echo "Chapter ZIP entry checks passed\n";
} finally {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $f) { if($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname()); } rmdir($dir);
}
