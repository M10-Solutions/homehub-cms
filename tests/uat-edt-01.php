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
$check(str_contains($index, '<h1 class="inline-heading-read"><?= e($pageTitle) ?></h1>'), 'Page title missing');
$check(!str_contains($index, 'inline-top') && str_contains($index, '<div class="inline-bottom"') && str_contains($index, 'class="inline-tabs"'), 'View buttons must be in footer');
$check(str_contains($index, 'class="inline-pane-header"') && str_contains($index, 'id="inline-status"'), 'Status must be in pane header');
$check(!str_contains($index, 'inline-edit-pill') && !str_contains($css, 'inline-edit-pill') && !str_contains($js, 'editPill') && !str_contains($js, 'inline-edit-pill'), 'Redundant edit pill must be absent');
echo "OK: UAT-EDT-01 compact viewport editor (static checks)\n";

require_once $root . 'lib/DocumentBody.php';
foreach (['', 'Beschreibung'] as $description) {
    foreach (["", "Text", "Text\n", "Text\n\n", "\nText", "\n\nText\n", "\n\n", "  ", "Text  \nWeiter"] as $body) {
        $stored = HomeHub\DocumentBody::compose('Titel', $description, $body);
        for ($i = 0; $i < 4; $i++) {
            $read = HomeHub\DocumentBody::extractContent("\n" . $stored, 'Titel', $description);
            $check($read === $body, 'Markdown whitespace changed');
            $check(HomeHub\DocumentBody::compose('Titel', $description, $read) === $stored, 'Repeated save changed document');
        }
    }
}
$check(HomeHub\DocumentBody::extractContent('Legacy content', 'Titel', '') === 'Legacy content', 'Legacy body changed');
$check(str_contains($index, 'data-inline-md="image" title="Web-Bild" aria-label="Web-Bild"'), 'Web-Bild label missing');
$check(str_contains($index, 'maxlength="1048576">' . "\n" . '<?= e($currentPage->content) ?>'), 'Textarea initial newline guard missing');
echo "OK: IDEA-20/21 whitespace round trips and toolbar label\n";

$check(str_contains($index, 'id="inline-title"') && str_contains($index, 'class="inline-heading-edit inline-metadata"') && str_contains($index, '<details id="inline-meta-details" class="inline-heading-edit inline-metadata" hidden>'), 'Editorial fields missing');
$check(str_contains($index, 'data-visibility-fields="roles"') && str_contains($index, 'data-visibility-fields="users"'), 'ACL groups missing');
$check(str_contains($js, 'hub-tag-reset') && str_contains($js, 'hub-tag-commit'), 'Tag lifecycle missing');
$check(str_contains(file_get_contents($root . 'lib/Admin.php'), '$lease->assertOwner'), 'Lease owner required');
$heading = substr($index, strpos($index, '<div class="page-heading">'), strpos($index, '<div class="page-heading-actions"') - strpos($index, '<div class="page-heading">'));
$check(str_contains($heading, 'id="inline-title"') && str_contains($heading, 'id="inline-description"') && str_contains($heading, 'id="hub-tag-fallback"'), 'Metadata must be in page heading');
$check(str_contains($css, '#inline-title{line-height:inherit;') && str_contains($css, 'font:inherit'), 'Title typography must be inherited');
echo "OK: IDEA-22/23 static contracts\n";

$check(str_contains($heading, "<summary>Beschreibung &amp; Schlagworte</summary>") && str_contains($js, "metaDetails.open=false") && str_contains($js, "metaDetails?.addEventListener('toggle',fitWorkspace)"), "Collapsible heading metadata missing");

$check(!str_contains($heading, 'class="eyebrow"') && !str_contains($heading, 'HOMEHUB · WISSENSBASIS'), 'Redundant page eyebrow must be absent');
