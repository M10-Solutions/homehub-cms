<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
require dirname(__DIR__).'/src/lib/TagRepository.php';
use HomeHub\TagRepository;
$dir = sys_get_temp_dir().'/hub-tag-admin-'.bin2hex(random_bytes(8));
function check(bool $ok): void { if (!$ok) throw new RuntimeException('TAG-003 assertion failed'); }
try {
    $a = new TagRepository($dir.'/tags.json');
    $a->change('tag-create', '', 'NAS');
    $b = new TagRepository($dir.'/tags.json');
    $b->change('tag-create', '', 'nas');
    $id = array_search('NAS', $a->all(), true);
    $a->change('tag-update', $id, 'Nas');
    check((new TagRepository($dir.'/tags.json'))->names([$id]) === ['Nas']);
    check(array_values((new TagRepository($dir.'/tags.json'))->all()) === ['Nas','nas']);
    foreach ([['tag-create','','nas'],['tag-update','missing','New'],['tag-delete','missing',''],['tag-create','','a,b']] as $args) {
        $failed = false;
        try { $a->change(...$args); } catch (RuntimeException) { $failed = true; }
        check($failed);
    }
    $a->change('tag-delete',$id,'');
    check(array_values((new TagRepository($dir.'/tags.json'))->all()) === ['nas']);
    $root = dirname(__DIR__).'/src/';
    check(str_contains(file_get_contents($root.'admin.php'), 'Nur Administratoren dürfen Schlagwörter verwalten'));
    check(str_contains(file_get_contents($root.'lib/TagAdministration.php'), 'isset($this->usage()[$original])'));
    check(str_contains(file_get_contents($root.'admin-tags-view.php'), 'hub-dialog-header'));
} finally { foreach (glob($dir.'/*') ?: [] as $file) unlink($file); if (is_dir($dir)) rmdir($dir); }
