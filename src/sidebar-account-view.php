<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
// Shared account footer; read current profile on every request.
$accountUsername = (string) $auth->currentUser();
$accountProfile = $auth->user($accountUsername) ?? [];
$accountName = trim(trim((string) ($accountProfile['first_name'] ?? '')) . ' ' . trim((string) ($accountProfile['last_name'] ?? '')));
$accountName = $accountName !== '' ? $accountName : $accountUsername;
$roleIcon = match ($role) { 'reader' => 'fa-book-open', 'editor' => 'fa-pen-to-square', 'admin' => 'fa-shield-halved', default => 'fa-user' };
$roleLabel = match ($role) { 'reader' => 'Leser', 'editor' => 'Editor', 'admin' => 'Administrator', default => 'Benutzer' };
$accountEscape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="sidebar-footer hub-sidebar-account" aria-label="Angemeldeter Benutzer">
    <span class="hub-sidebar-account-icon" role="img" aria-label="<?= $accountEscape($roleLabel) ?>" title="<?= $accountEscape($roleLabel) ?>"><i class="fa-solid <?= $accountEscape($roleIcon) ?>" aria-hidden="true"></i></span>
    <span class="hub-sidebar-account-details"><strong class="hub-sidebar-account-name"><?= $accountEscape($accountName) ?></strong><span class="hub-sidebar-account-username"><?= $accountEscape($accountUsername) ?></span></span>
</div>
