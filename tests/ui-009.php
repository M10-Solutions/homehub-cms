<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root=dirname(__DIR__).'/src/';
$index=file_get_contents($root.'index.php');
preg_match('~<dialog id="hub-chapter-export-dialog".*?</dialog>~s',$index,$m);
if(substr_count($m[0]??'','hub-action-btn')!==4 || substr_count($m[0]??'','hub-action-btn primary')!==3 || !str_contains($index,'admin-buttons.css?v=4')) throw new RuntimeException('Export buttons missing');
foreach(['pdf','odt','zip'] as $format) if(!str_contains($m[0],'name="format" value="'.$format.'"')) throw new RuntimeException('Format lost');
if(!str_contains(file_get_contents($root.'css/admin-buttons.css'),'.hub-action-btn')) throw new RuntimeException('Shared styles missing');
echo "UI-009 shared export buttons checked\n";
