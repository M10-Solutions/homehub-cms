<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use HomeHub\{PageRepository, PermissionService, PageMetadata};
$root = sys_get_temp_dir() . '/hh-sec-' . bin2hex(random_bytes(8));
mkdir($root . '/content/chapter', 0700, true);
mkdir($root . '/config', 0700);
function check(bool $ok, string $msg): void { if (!$ok) throw new RuntimeException($msg); }
try {
    $repo = new PageRepository($root . '/content');
    file_put_contents($root . '/content/chapter/child.md', "---\ntitle: Child\npublished: true\n---\nChild");
    foreach (["---\nvisibility: [\n---\nSecret", "---\nvisibility: roles\nSecret", "---\n- all\n---\nSecret", "---\nvisibility: null\n---\nSecret", "---\npublished: 'false'\n---\nSecret", "---\nallowed_roles: admin\n---\nSecret", "\xEF\xBB\xBF---\nvisibility: [\n---\nSecret"] as $raw) {
        file_put_contents($root . '/content/chapter.md', $raw);
        $page = $repo->findForAccess('chapter');
        check($page !== null && !$page->metadataValid && $page->content === '', 'invalid sentinel');
        check($repo->find('chapter') === null, 'public find');
        foreach ([null,'reader','editor','admin'] as $role) check(!PermissionService::canReadDraft($page,$role,$repo), 'invalid draft');
        foreach ([null,'reader','editor'] as $role) check(!PermissionService::canReadPage($repo->find('chapter/child'),$role,$repo), 'inherited denial');
        check(!PermissionService::canCreateAt('chapter/new','editor',$repo,null), 'create denied');
        check(count($repo->all()) === 1, 'invalid excluded');
        try { PageMetadata::parse($raw); throw new LogicException('parser accepted invalid'); } catch (Exception $e) { if ($e instanceof LogicException) throw $e; }
    }
    file_put_contents($root . '/content/chapter.md', "---\nvisibility: roles\nallowed_roles: [editor]\npublished: false\n---\nChapter");
    check(!PermissionService::canReadPage($repo->find('chapter/child'),'reader',$repo), 'valid ACL denied');
    check(PermissionService::canReadPage($repo->find('chapter/child'),'editor',$repo), 'valid ACL allowed');
    file_put_contents($root . '/content/chapter.md', '# Legacy');
    check(PermissionService::canReadPage($repo->find('chapter/child'),null,$repo), 'legacy remains public');
    echo "Security metadata checks OK\n";
} finally {
    unlink($root.'/content/chapter/child.md'); unlink($root.'/content/chapter.md'); rmdir($root.'/content/chapter'); rmdir($root.'/content'); rmdir($root.'/config'); rmdir($root);
}
