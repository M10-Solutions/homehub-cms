<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
/** UAT-002c.1 navigation semantics contract test. */
$root=dirname(__DIR__);
$index=file_get_contents($root.'/src/index.php');
$css=file_get_contents($root.'/src/css/page-actions.css');
$checks=[
 'drafts independent of nav'=>str_contains($index, '($page->showInNavigation || ($showDrafts && !$page->published))'),
 'hidden flag propagated'=>str_contains($index, '\'hidden_in_navigation\' => !$page->showInNavigation'),
 'hidden draft icon'=>str_contains($index, 'hub-draft-hidden') && str_contains($index, 'fa-eye-slash'),
 'form explanation'=>str_contains($index, 'Bestimmt, ob die Seite nach der Veröffentlichung'),
 'theme-aware icon style'=>str_contains($css, '.hub-draft-hidden'),
];
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}}
echo "UAT-002c.1 nav semantics OK\n";
