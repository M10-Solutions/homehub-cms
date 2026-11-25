<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root = dirname(__DIR__) . '/src/';
$admin = file_get_contents($root . 'admin.php');
$media = file_get_contents($root . 'js/admin-media.js');
$checks = [
    !str_contains($admin, 'id="editor-form"'),
    !str_contains($admin, 'renderPageTree('),
    !str_contains($admin, "'save' => 'page.edit'"),
    !str_contains($admin, "if (\$action === 'delete')"),
    !str_contains($admin, '/js/editor.js'),
    str_contains($admin, 'Benutzerverwaltung'),
    str_contains($admin, 'Medienverwaltung'),
    str_contains($admin, 'admin-media.js'),
    str_contains($admin, 'Admin::slugIsValid'),
    str_contains($media, 'Medien endgültig löschen?'),
    !str_contains($media, 'delete-media-form'),
    str_contains($media, 'copy-markdown'),
    str_contains($media, 'media-filter'),
    !is_file($root . 'js/editor.js'),
    is_file($root . 'inline.php'),
    is_file($root . 'page-actions.php'),
];
foreach ($checks as $i => $ok) if (!$ok) { fwrite(STDERR, "ADMIN-001d check #$i failed\n"); exit(1); }
echo "ADMIN-001d structural regression checks passed\n";
