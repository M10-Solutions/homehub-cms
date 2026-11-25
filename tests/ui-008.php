<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
 declare(strict_types=1);
$root=dirname(__DIR__).'/src/';
$count=0;
foreach(['index.php','admin.php','admin-users-view.php','media-picker-view.php'] as $file){
 preg_match_all('~<dialog\b.*?</dialog>~s',file_get_contents($root.$file),$matches);
 foreach($matches[0] as $dialog){
  if(substr_count($dialog,'hub-dialog-header')!==1) throw new RuntimeException('Header missing or duplicated: '.$file);
  ++$count;
 }
}
if($count!==12) throw new RuntimeException('Unexpected dialog count');
foreach(['index.php','admin.php'] as $file) if(!str_contains(file_get_contents($root.$file),'dialog-header.css?v=1')) throw new RuntimeException('Missing stylesheet');
if(!str_contains(file_get_contents($root.'js/page-picker.js'),'hh-page-picker-head hub-dialog-header')) throw new RuntimeException('Picker header missing');
if(!str_contains(file_get_contents($root.'css/dialog-header.css'),'var(--hub-header-bg')) throw new RuntimeException('Brand colour missing');
echo "UI-008 dialog header checks passed\n";
