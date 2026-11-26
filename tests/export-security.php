<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
use HomeHub\{Page, DocumentExporter, ChapterZipExporter, MediaRegistry};
$dir = sys_get_temp_dir().'/hh-export-sec-'.bin2hex(random_bytes(8));
foreach (['content','upload','config'] as $folder) mkdir($dir.'/'.$folder,0700,true);
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
try {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
    foreach (['ok','unused','excluded','code'] as $name) file_put_contents($dir.'/upload/'.$name.'.png',$png);
    $registry = new MediaRegistry($dir.'/config');
    $url = '/media.php?id='.$registry->register('ok.png');
    $body = "![valid][ok]\n\n[ok]: $url\n\n[unused]: /media.php?file=unused.png\n\n![local](file:///etc/passwd)\n\n![remote][remote]\n\n[remote]: https://example.invalid/image.png\n\n![bad]($url&context=library)\n\n![legacy](/media.php?file=ok.png)\n\n```md\n![code](/media.php?file=code.png)\n```\n\n<img src=\"file:///etc/passwd\">\n";
    $page = new Page('root','Root','',$body,'',1,true,true);
    file_put_contents($dir.'/content/root.md',$body);
    file_put_contents($dir.'/content/excluded.md','![excluded](/media.php?file=excluded.png)');
    $exporter = new DocumentExporter($dir.'/upload');
    $html = $exporter->preparePage($page);
    $dom = new DOMDocument(); $dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $images = $dom->getElementsByTagName('img');
    check($images->length === 2, 'Only two valid images expected');
    foreach ($images as $image) check($image->getAttribute('src') === $dir.'/upload/ok.png', 'Unexpected resource');
    $entries = [];
    (new ChapterZipExporter($dir.'/content',$dir.'/upload',$dir.'/config'))->collect([$page],static function($n,$s)use(&$entries){$entries[$n]=$s;},static function($n,$p)use(&$entries){$entries[$n]=file_get_contents($p);});
    check(isset($entries['media/ok.png']) && $entries['media/ok.png']===$png, 'Valid media missing');
    foreach (['unused','excluded','code'] as $name) check(!isset($entries['media/'.$name.'.png']), 'Unexpected ZIP media: '.$name);
    check(!isset($entries['pages/excluded.md']), 'Excluded page present');
    check(str_contains($entries['pages/root.md'],'[ok]: ../media/ok.png'), 'Reference definition not rewritten');
    echo "Export resource selection checks passed\n";
} finally {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $f) { if($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname()); } rmdir($dir);
}
