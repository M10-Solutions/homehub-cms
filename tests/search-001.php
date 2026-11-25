<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
foreach (['Page','PageSearch','TagRepository','PermissionService'] as $class) require dirname(__DIR__).'/src/lib/'.$class.'.php';
use HomeHub\Page;
use HomeHub\PageSearch;
use HomeHub\PermissionService;
use HomeHub\TagRepository;
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function page(string $slug, string $title, string $body, array $tags = [], bool $published = true, string $visibility = 'all', string $description = ''): Page {
    return new Page($slug,$title,$description,$body,'',100,false,$published,$tags,$visibility);
}
$dir = sys_get_temp_dir().'/hh-search-'.bin2hex(random_bytes(8));
mkdir($dir,0700);
try {
    $repo = new TagRepository($dir.'/tags.json');
    [$id] = $repo->assign(['Netzwerk']);
    $repo->change('tag-update',$id,'Netzwerktechnik');
    $pages = [page('body','Router',str_repeat('ä ',100).'NETZWERK Verbindung'),page('title','Netzwerk Übersicht','Inhalt'),page('tag','Switch','Inhalt',$repo->names([$id])),page('draft','Netzwerk Entwurf','',[],false),page('secret','Netzwerk geheim','',[],true,'authenticated'),page('desc','Beschreibung','',[],true,'all','Netzwerk'),page('other','Überblick','C++ und [Link](/page.php?id=secretid)'),page('missing','Verwaist','',['t_'.str_repeat('a',32)])];
    $allowed = array_values(array_filter($pages,static fn(Page $p): bool => PermissionService::canReadPage($p,null)));
    $results = PageSearch::find($allowed,'NETZWERK');
    check(array_map(static fn($r)=>$r['page']->slug,$results) === ['title','tag','desc','body'],'ranking and public permissions');
    check($results[1]['tags'] === ['Netzwerktechnik'],'central rename');
    check(str_contains($results[3]['excerpt'],'NETZWERK') && preg_match('//u',$results[3]['excerpt'])===1,'UTF-8 excerpt');
    check(count(PageSearch::find($allowed,'netzwerk VERBINDUNG'))===1,'all words');
    check(count(PageSearch::find($allowed,'ÜBERBLICK'))===1,'Unicode case folding');
    check(count(PageSearch::find($allowed,'C++'))===1,'literal regex characters');
    check(PageSearch::find($allowed,'secretid')===[],'link targets excluded');
    check(PageSearch::find($allowed,'t_aaaa')===[],'unknown IDs excluded');
    check(PageSearch::find($allowed,'   ')===[] && PageSearch::query(['x'])==='', 'empty or invalid query');
    check(strlen(PageSearch::query(str_repeat('x',200)))===160,'query length cap');
    check(count(PageSearch::find($pages,'Entwurf'))===0,'draft exclusion');
    $index = file_get_contents(dirname(__DIR__).'/src/index.php');
    check(str_contains($index,'PageSearch::find($allPages, $searchQuery)') && str_contains($index,'PermissionService::canReadPage($page, $role, $pages, $userId)'), 'permission-filtered call');
    $view = file_get_contents(dirname(__DIR__).'/src/search-results-view.php');
    check(str_contains($view,'e($result[\'excerpt\'])') && str_contains($view,'e($page->title)'),'escape output');
    if (is_file(dirname(__DIR__).'/vendor/autoload.php')) {
        require dirname(__DIR__).'/vendor/autoload.php';
        mkdir($dir.'/content'); mkdir($dir.'/config'); mkdir($dir.'/content/protected');
        rename($dir.'/tags.json',$dir.'/config/tags.json');
        file_put_contents($dir.'/content/protected.md',"---\ntitle: Parent\nvisibility: authenticated\n---\nGeheim");
        file_put_contents($dir.'/content/protected/child.md',"---\ntitle: Child\n---\nGeheim");
        file_put_contents($dir.'/content/public.md',"---\ntitle: Public\nlabels: [$id]\nnav: false\n---\nÖffentlich");
        $repository = new HomeHub\PageRepository($dir.'/content');
        $visible = array_values(array_filter($repository->all(),static fn($p)=>PermissionService::canReadPage($p,null,$repository)));
        check(PageSearch::find($visible,'Geheim')===[],'inherited restriction');
        check(count(PageSearch::find($visible,'Netzwerktechnik'))===1,'resolved tag on hidden-navigation page');
    } else echo "YAML integration unavailable\n";
} finally {
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $entry) { if ($entry->isDir()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); } rmdir($dir);
}
