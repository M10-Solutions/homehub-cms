<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/** UAT-002c.1: draft visibility and editor ACL, no public export/search leakage. */
$root=dirname(__DIR__);
$read=static fn(string $p):string=>file_get_contents($root.'/'.$p);
$check=static function(bool $ok,string $m):void {if(!$ok){fwrite(STDERR,"FAIL: $m\n");exit(1);}};
$p=$read('src/lib/PermissionService.php');$i=$read('src/index.php');$a=$read('src/lib/Admin.php');$in=$read('src/inline.php');
$check(str_contains($p,"'editor' => ['page.read', 'page.read_draft'"),'editor draft permission');
$check(str_contains($p,'function canReadDraft') && str_contains($p,'self::matchesVisibility($ancestor'),'inherited ACL');
$check(str_contains($i,'PermissionService::canReadDraft($draftPage'),'draft route checked');
$check(str_contains($i,'PermissionService::canReadDraft($page, $role, $pages, $userId)'),'draft navigation checked');
$check(str_contains($i,'hub-nav-is-draft') && str_contains($i,'>Draft</span>'),'draft presentation');
$check(str_contains($in,'PermissionService::canReadDraft'),'inline endpoint checked');
$check(str_contains($a,'PermissionService::canReadDraft'),'content-only save checked');
$check(str_contains($read('src/lib/PageSearch.php'),'!$page->published'),'search excludes drafts');
$check(str_contains($read('src/lib/PageRepository.php'),'return ($page !== null && $page->published) ? $page : null;'),'public find excludes drafts');
echo "UAT-002c.1 contract checks OK\n";
