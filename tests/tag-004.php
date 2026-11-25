<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
foreach (['TagRepository','TagAdministration','MediaOperationLock'] as $class) require dirname(__DIR__).'/src/lib/'.$class.'.php';
use HomeHub\TagRepository;
use HomeHub\TagAdministration;
$dir = sys_get_temp_dir().'/hh-tag-id-'.bin2hex(random_bytes(8));
mkdir($dir.'/config',0700,true); mkdir($dir.'/content');
function check(bool $ok): void { if (!$ok) throw new RuntimeException('TAG-004 assertion failed'); }
try {
    $repo = new TagRepository($dir.'/config/tags.json');
    [$id] = $repo->assign(['Netzwerk']);
    $raw = "---\ntitle: Router\nlabels:\n  - $id\n---\nInhalt\n";
    foreach (['router','firewall'] as $slug) file_put_contents($dir.'/content/'.$slug.'.md',$raw);
    $admin = new TagAdministration($dir.'/content',$dir.'/config');
    $admin->change('tag-update',$id,'Netzwerktechnik');
    $fresh = new TagRepository($dir.'/config/tags.json');
    check($fresh->all() === [$id=>'Netzwerktechnik']);
    check($fresh->assign([$id]) === [$id]);
    check($fresh->names([$id]) === ['Netzwerktechnik']);
    foreach (['router','firewall'] as $slug) check(file_get_contents($dir.'/content/'.$slug.'.md') === $raw);
    $json = json_decode(file_get_contents($dir.'/config/tags.json'),true);
    check($json === [['id'=>$id,'name'=>'Netzwerktechnik']]);
    if (is_file(dirname(__DIR__).'/vendor/autoload.php')) {
        require dirname(__DIR__).'/vendor/autoload.php';
        check($admin->all()[0]['count'] === 2);
        $blocked = false;
        try { $admin->change('tag-delete',$id,''); } catch (RuntimeException) { $blocked = true; }
        check($blocked);
        $pages = new HomeHub\PageRepository($dir.'/content');
        check($pages->find('router')->labels === ['Netzwerktechnik']);
        mkdir($dir.'/upload');
        $entries = [];
        (new HomeHub\ChapterZipExporter($dir.'/content',$dir.'/upload',$dir.'/config'))->collect([$pages->find('router')],function($name,$body)use(&$entries){$entries[$name]=$body;},static function(){});
        check(str_contains($entries['pages/router.md'],'Netzwerktechnik') && !str_contains($entries['pages/router.md'],$id));
    } else echo "Integration with YAML/Markdown dependencies not available\n";
    check($fresh->names(['Legacy']) === ['Legacy']);
    file_put_contents($dir.'/config/tags.json','["Legacy"]');
    $blocked = false;
    try { (new TagRepository($dir.'/config/tags.json'))->all(); } catch (RuntimeException) { $blocked = true; }
    check($blocked && file_get_contents($dir.'/config/tags.json') === '["Legacy"]');
} finally {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $f) { if($f->isDir()) rmdir($f->getPathname()); else unlink($f->getPathname()); } rmdir($dir);
}
