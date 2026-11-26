<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
if (!isset($isAdmin) || !$isAdmin) { http_response_code(403); exit; }
$trash = new \HomeHub\PageTrash($config['content_path'], dirname(__DIR__) . '/data/config');
$entries = $trash->entries();
?>
<h1>Papierkorb</h1>
<p>Wiederherstellung am ursprünglichen Ort. Dabei gelten die aktuellen geerbten Kapitelberechtigungen.</p>
<section class="users-manager" id="trash-manager">
<div class="users-toolbar">
<button type="button" class="admin-btn" data-trash-action="trash-restore" disabled><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Wiederherstellen</span></button>
<button type="button" class="admin-btn danger" data-trash-action="trash-purge" disabled><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span>Endgültig löschen</span></button>
<button type="button" class="admin-btn danger" data-trash-action="trash-empty" disabled><i class="fa-solid fa-trash" aria-hidden="true"></i><span>Papierkorb leeren</span></button>
<span id="trash-count" aria-live="polite">0 ausgewählt</span>
</div>
<div class="users-table-wrap"><table class="users-table"><thead><tr>
<th scope="col"><input type="checkbox" id="trash-select-all" aria-label="Alle Einträge auswählen"<?= !$entries ? ' disabled' : '' ?>></th><th scope="col">Ursprünglicher Pfad</th><th scope="col">Gelöscht am</th><th scope="col">Benutzer</th><th scope="col">Seiten</th>
</tr></thead><tbody>
<?php foreach ($entries as $id => $entry): ?>
<tr><td><input type="checkbox" data-trash-id value="<?= h($id) ?>" aria-label="<?= h($entry['slug']) ?> auswählen"></td><td><i class="fa-solid <?= count($entry['pages']) > 1 ? 'fa-folder-tree' : 'fa-file-lines' ?>" aria-hidden="true"></i> <?= h($entry['slug']) ?></td><td><?= h((new DateTimeImmutable($entry['date']))->format('d.m.Y, H:i')) ?></td><td><?= h($entry['actor']) ?></td><td><?= count($entry['pages']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php if (!$entries): ?><p>Der Papierkorb ist leer.</p><?php endif; ?>
<dialog class="media-dialog" id="trash-confirm" aria-labelledby="trash-confirm-title"><form method="post" action="/admin.php?trash=1">
<h2 class="hub-dialog-header" id="trash-confirm-title"><i class="fa-solid fa-trash" aria-hidden="true"></i> <span class="hub-dialog-title-text"></span></h2>
<p id="trash-confirm-text"></p><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action"><div id="trash-confirm-ids"></div>
<label class="users-active"><input type="checkbox" id="trash-confirm-check" value="yes" required> <span id="trash-confirm-label"></span></label>
<div class="media-dialog-actions"><button class="admin-btn" type="button" id="trash-cancel">Abbrechen</button><button class="admin-btn" type="submit" id="trash-submit">Bestätigen</button></div>
</form></dialog><noscript>Die Papierkorbverwaltung benötigt JavaScript.</noscript></section>
<script src="/js/admin-trash.js?v=1" defer></script>
