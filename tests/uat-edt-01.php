<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);
$root = dirname(__DIR__) . '/src/';
$index = file_get_contents($root . 'index.php');
$js = file_get_contents($root . 'js/inline-editor.js');
$css = file_get_contents($root . 'css/inline-editor.css');
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$check(!str_contains($index, 'id="inline-sticky-actions"'), 'Old sticky bar still present');
$check(str_contains($index, 'class="inline-bottom"') && str_contains($index, 'id="inline-save"') && str_contains($index, 'id="inline-cancel"'), 'Actions must be inside workspace footer');
$check(str_contains($index, 'data-inline-view="preview"') && str_contains($index, 'fa-eye'), 'Icon view selector missing');
$check(str_contains($css, '100dvh') && str_contains($css, 'body.inline-edit-active .page-heading{display:flex}'), 'Viewport layout missing');
$check(str_contains($js, "document.body.classList.add('inline-edit-active')") && str_contains($js, "document.body.classList.remove('inline-edit-active')"), 'Edit layout lifecycle missing');
$check(str_contains($css, 'grid-template-rows:minmax(0,1fr)') && str_contains($css, 'overflow-y:auto'), 'Equal height scrollable panels missing');
$check(str_contains($js, 'fitWorkspace()') && str_contains($js, '--inline-available-height'), 'Viewport fitting missing');
$check(str_contains($js, "document.querySelector('.page-heading')?.scrollIntoView({block:'start',behavior:'instant'})") && str_contains($js, 'textarea.focus({preventScroll:true})'), 'Editor focus or positioning missing');
$check(str_contains($index, 'class="inline-edit-pill"') && str_contains($index, '<h1><?= e($pageTitle) ?></h1>'), 'Page title and edit pill missing');
$check(!str_contains($index, 'inline-top') && str_contains($index, '<div class="inline-bottom"') && str_contains($index, 'class="inline-tabs"'), 'View buttons must be in footer');
$check(str_contains($index, 'class="inline-pane-header"') && str_contains($index, 'id="inline-status"'), 'Status must be in pane header');
$check(str_contains($js, 'editPill.hidden=false') && str_contains($js, 'editPill.hidden=true'), 'Edit pill lifecycle missing');
echo "OK: UAT-EDT-01 compact viewport editor (static checks)\n";
