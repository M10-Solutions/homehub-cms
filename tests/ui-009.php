<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/';
$index=file_get_contents($root.'index.php');
preg_match('~<dialog id="hub-chapter-export-dialog".*?</dialog>~s',$index,$m);
$dialog=$m[0]??'';
if(substr_count($dialog,'hub-action-btn')!==2 || substr_count($dialog,'hub-action-btn primary')!==1 || !str_contains($index,'admin-buttons.css?v=4')) throw new RuntimeException('Export buttons missing');
foreach(['pdf','odt','zip'] as $format) if(!str_contains($dialog,'<option value="'.$format.'">')) throw new RuntimeException('Format lost');
if (!str_contains($dialog,'Seite oder Kapitel exportieren') || strpos($dialog,'name="format"') >= strpos($dialog,'name="scope"') || !str_contains($dialog,'value="page" checked') || !str_contains($dialog,'id="hub-export-document-options" hidden disabled')) throw new RuntimeException('Export dialog defaults');
if(!str_contains(file_get_contents($root.'css/admin-buttons.css'),'.hub-action-btn')) throw new RuntimeException('Shared styles missing');
if (!str_contains($index,'!$isDraftView && !$auth->isSigned()') || !str_contains($index,'!$isDraftView && $auth->isSigned()') || str_contains($index,'Als ODT herunterladen')) throw new RuntimeException('Export actions');
foreach (['chapter-export.php','export.php'] as $file) {
    $source=file_get_contents($root.$file);
    if (!str_contains($source,'!$auth->isSigned()') || !str_contains($source,'PermissionService::canReadPage')) throw new RuntimeException('Export access guard missing');
}
if (!str_contains(file_get_contents($root.'chapter-export.php'),"\$scope === 'page' ? [] : [")) throw new RuntimeException('Single page options');
echo "UI-009 / IDEA-25 export dialog and access guard source checks passed\n";
