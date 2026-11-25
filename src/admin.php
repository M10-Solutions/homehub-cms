<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\Admin;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/config/app.php';
\HomeHub\Session::start();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; style-src \'self\'; img-src \'self\' data:; base-uri \'none\'; form-action \'self\'');
$media = new \HomeHub\MediaLibrary(dirname(__DIR__) . '/data/upload');
$mediaRegistry = new \HomeHub\MediaRegistry(dirname(__DIR__) . '/data/config');
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function requireCsrf(): void {
    if (!hash_equals(csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403); throw new RuntimeException('Ungültiges Sicherheitstoken. Bitte Seite neu laden.');
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $expected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
        if (!hash_equals($expected, $origin)) {
            http_response_code(403); throw new RuntimeException('Ungültiger Ursprung.');
        }
    }
}
function redirectAdmin(string $query = ''): never { header('Location: /admin.php' . $query, true, 303); exit; }
$auth = new \HomeHub\JSONAuthenticator(dirname(__DIR__) . '/data/config');
$auth->ensureUserIds();
$loggedIn = $auth->isSigned();
$role = $auth->currentRole();
$isAdmin = \HomeHub\PermissionService::can($role, 'user.manage');
$canMedia = \HomeHub\PermissionService::can($role, 'media.manage');
$showUsers = $loggedIn && $isAdmin && isset($_GET['users']);
$showTags = $loggedIn && $isAdmin && isset($_GET['tags']);
$error = '';
$notice = '';
$action = (string) ($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrf();
        if ($action === 'login') {
            $last = (int) ($_SESSION['last_login_attempt'] ?? 0);
            if (time() - $last < 2) throw new RuntimeException('Bitte kurz warten und erneut versuchen.');
            $_SESSION['last_login_attempt'] = time();
            if (!$auth->login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''))) {
                sleep(1);
                throw new RuntimeException('Benutzername oder Passwort falsch.');
            }
            redirectAdmin();
        }
        if (!$loggedIn) { http_response_code(403); throw new RuntimeException('Bitte zuerst anmelden.'); }
        if ($action === 'logout') {
            $auth->logout();
            $_SESSION = [];
            session_regenerate_id(true);
            redirectAdmin();
        }
        if (in_array($action, ['user-create', 'user-update', 'user-delete'], true)) {
            if (!$isAdmin) { http_response_code(403); throw new RuntimeException('Nur Administratoren dürfen Benutzer verwalten.'); }
            $original = (string) ($_POST['original_username'] ?? '');
            $username = (string) ($_POST['username'] ?? '');
            $profile = array_intersect_key($_POST, array_flip(['first_name', 'last_name', 'email']));
            $ownAccount = $original === $auth->currentUser();
            if ($action === 'user-create') $auth->registerUser($username, (string) ($_POST['password'] ?? ''), (string) ($_POST['role'] ?? 'reader'), isset($_POST['active']), $profile);
            elseif ($action === 'user-update') {
                $auth->updateUser($original, $username, (string) ($_POST['password'] ?? ''), (string) ($_POST['role'] ?? 'reader'), isset($_POST['active']), $profile);
                if ($ownAccount && $original !== trim($username)) $_SESSION['user'] = trim($username);
            } else {
                if ($original === $auth->currentUser()) throw new RuntimeException('Eigenes Konto kann nicht gelöscht werden.');
                $auth->deleteUser($original);
            }
            redirectAdmin('?users=1&updated=1');
        }
        if (in_array($action, ['tag-create', 'tag-update', 'tag-delete'], true)) {
            if (!$isAdmin) { http_response_code(403); throw new RuntimeException('Nur Administratoren dürfen Schlagwörter verwalten.'); }
            $tagAdmin = new \HomeHub\TagAdministration($config['content_path'], dirname(__DIR__) . '/data/config');
            $tagAdmin->change($action, (string) ($_POST['original_tag'] ?? ''), (string) ($_POST['tag'] ?? ''));
            redirectAdmin('?tags=1&tags_updated=' . rawurlencode($action));
        }
        $permission = match ($action) {
            'upload', 'delete-media', 'create-media-folder', 'move-media', 'bulk-media' => 'media.manage',
            default => null,
        };
        if ($permission === null) throw new RuntimeException('Unbekannte Aktion.');
        if (!\HomeHub\PermissionService::can($role, $permission)) {
            http_response_code(403);
            throw new RuntimeException('Für diese Aktion fehlen die Berechtigungen.');
        }
        if ($action === 'upload') {
            $folder = (string) ($_POST['folder'] ?? '');
            $uploads = $_FILES['files'] ?? $_FILES['file'] ?? [];
            $items = [];
            if (is_array($uploads['name'] ?? null)) {
                foreach ($uploads['name'] as $i => $filename) {
                    $item = [];
                    foreach (['name', 'tmp_name', 'error', 'size', 'type'] as $key) $item[$key] = $uploads[$key][$i] ?? null;
                    $items[] = $item;
                }
            } elseif ($uploads) $items[] = $uploads;
            if (!$items || count($items) > 20) throw new RuntimeException('Bitte 1 bis 20 Dateien auswählen.');
            if (array_sum(array_column($items, 'size')) > 10 * 1024 * 1024) throw new RuntimeException('Maximal 10 MB pro Upload.');
            $done = 0; $failures = [];
            foreach ($items as $item) {
                try {
                    $name = $media->upload($item, $folder);
                    $mediaRegistry->register($name);
                    ++$done;
                } catch (Throwable $e) {
                    $failures[] = basename((string) ($item['name'] ?? 'Datei')) . ': ' . $e->getMessage();
                }
            }
            $_SESSION['media_batch_notice'] = $done . ' Dateien hochgeladen.';
            $_SESSION['media_batch_error'] = implode(' | ', $failures);
            redirectAdmin('?media=1&folder=' . rawurlencode($folder));
        }
        if ($action === 'bulk-media') {
            $folder = (string) ($_POST['folder'] ?? '');
            $target = (string) ($_POST['target_folder'] ?? '');
            $operation = (string) ($_POST['operation'] ?? '');
            $files = json_decode((string) ($_POST['selection'] ?? '[]'), true);
            if (!is_array($files) || !$files || !in_array($operation, ['move', 'delete'], true)) throw new RuntimeException('Keine gültige Auswahl.');
            $folders = $media->folders();
            if (!in_array($folder, $folders, true) || ($operation === 'move' && (!in_array($target, $folders, true) || $target === $folder))) throw new RuntimeException('Ungültiger Ziel- oder Quellordner.');
            $batch = new \HomeHub\MediaBatch($media, $mediaRegistry, new \HomeHub\MediaOperationLock(dirname(__DIR__) . '/data/config'));
            [$done, $failures] = $batch->run($operation, $folder, $target, $files);
            $_SESSION['media_batch_notice'] = $done === 0 ? '' : $done . ($operation === 'delete' ? ($done === 1 ? ' Mediendatei gelöscht.' : ' Mediendateien gelöscht.') : ($done === 1 ? ' Mediendatei verschoben.' : ' Mediendateien verschoben.'));
            $_SESSION['media_batch_notice_type'] = $operation === 'delete' ? 'delete' : 'success';
            $_SESSION['media_batch_error'] = implode(' | ', $failures);
            redirectAdmin('?media=1&folder=' . rawurlencode($folder));
        }
        if ($action === 'create-media-folder') {
            $parent = (string) ($_POST['folder'] ?? '');
            $name = (string) ($_POST['folder_name'] ?? '');
            if (!in_array($parent, $media->folders(), true) || !\HomeHub\MediaLibrary::validName($name)) throw new RuntimeException('Ungültiger Ordnername.');
            $newFolder = ltrim($parent . '/' . $name, '/');
            if (in_array($newFolder, $media->folders(), true)) throw new RuntimeException('Ordner existiert bereits.');
            $media->createFolder($newFolder);
            redirectAdmin('?media=1&folder=' . rawurlencode($newFolder));
        }
        if ($action === 'move-media') {
            $targetFolder = (string) ($_POST['target_folder'] ?? '');
            (new \HomeHub\MediaMover($media, $mediaRegistry, new \HomeHub\MediaOperationLock(dirname(__DIR__) . '/data/config')))
                ->move((string) ($_POST['file'] ?? ''), $targetFolder, (string) ($_POST['target_name'] ?? ''));
            redirectAdmin('?media=1&folder=' . rawurlencode($targetFolder) . '&media_moved=1');
        }
        if ($action === 'delete-media') {
            (new \HomeHub\MediaDeleter($media, $mediaRegistry, new \HomeHub\MediaOperationLock(dirname(__DIR__) . '/data/config')))
                ->delete((string) ($_POST['file'] ?? ''));
            redirectAdmin('?media=1&folder=' . rawurlencode((string) ($_POST['folder'] ?? '')) . '&removed=1');
        }
        throw new RuntimeException('Unbekannte Aktion.');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
// Legacy editor URLs now lead to the single Content workspace.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && (isset($_GET['page']) || isset($_GET['new']))) {
    $slug = trim((string) ($_GET['page'] ?? ''), '/');
    if ($slug !== '' && \HomeHub\Admin::slugIsValid($slug)) {
        header('Location: /' . implode('/', array_map('rawurlencode', explode('/', $slug))), true, 302);
    } else {
        header('Location: /', true, 302);
    }
    exit;
}
if (isset($_GET['tags_updated'])) {
    $notice = match ((string) $_GET['tags_updated']) {
        'tag-delete' => 'Schlagwort gelöscht.',
        'tag-create' => 'Schlagwort erstellt.',
        'tag-update' => 'Schlagwort aktualisiert.',
        default => 'Schlagwortverwaltung aktualisiert.',
    };
}
if (isset($_GET['updated'])) $notice = 'Benutzerverwaltung aktualisiert.';
if (isset($_GET['uploaded'])) $notice = 'Datei hochgeladen.';
if (isset($_GET['removed'])) $notice = 'Datei gelöscht.';
if (isset($_GET['media_moved'])) $notice = 'Medium verschoben.';
if (isset($_SESSION['media_batch_notice'])) { $notice = $_SESSION['media_batch_notice']; $error = $_SESSION['media_batch_error']; unset($_SESSION['media_batch_notice'], $_SESSION['media_batch_error']); }
$noticeType = (isset($_GET['removed']) || (isset($_GET['tags_updated']) && $_GET['tags_updated'] === 'tag-delete')) ? 'delete' : ($_SESSION['media_batch_notice_type'] ?? 'success');
unset($_SESSION['media_batch_notice_type']);
$showMedia = $loggedIn && $canMedia && isset($_GET['media']);
function adminBreadcrumbs(bool $showMedia, bool $showUsers, bool $loggedIn, bool $showTags = false): array {
    $label = !$loggedIn ? 'Anmeldung' : ($showTags ? 'Schlagwörter' : ($showUsers ? 'Benutzerverwaltung' : ($showMedia ? 'Medienverwaltung' : null)));
    $crumbs = [['label' => 'Verwaltung', 'href' => $label === null ? null : '/admin.php']];
    if ($label !== null) $crumbs[] = ['label' => $label, 'href' => null];
    return $crumbs;
}
?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>HomeHub · Verwaltung</title>
    <link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css?v=6.7.2"><link rel="stylesheet" href="/css/admin.css?v=2&amp;theme=1"><link rel="stylesheet" href="/css/admin-media.css?v=4&amp;theme=1"><link rel="stylesheet" href="/css/admin-navigation.css?v=2&amp;theme=1"><link rel="stylesheet" href="/css/admin-unified.css?v=4&amp;theme=1"><link rel="stylesheet" href="/css/layout.css?v=3&amp;theme=1"><link rel="stylesheet" href="/css/navigation.css?v=3&amp;theme=1">
<link rel="stylesheet" href="/css/fontawesome-ui.css?v=2"><link rel="stylesheet" href="/css/admin-users.css?v=1&amp;theme=1"><link rel="stylesheet" href="/css/admin-buttons.css?v=3&amp;theme=1"><link rel="stylesheet" href="/css/sidebar-account.css?v=3&amp;theme=1"><link rel="stylesheet" href="/css/dialog-header.css?v=1"><link rel="stylesheet" href="/css/theme.css?v=1"><link rel="stylesheet" href="/css/hub-feedback.css?v=2">
<link rel="stylesheet" href="/css/responsive-navigation.css?v=1">
<script src="/js/responsive-navigation.js?v=1" defer></script>
<script src="/js/hub-feedback.js?v=2" defer></script><script src="/js/theme.js?v=1"></script>
</head>
<body class="hub-admin"><header class="admin-top hub-header"><?php if ($loggedIn): ?><button type="button" class="hub-mobile-nav-toggle" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Navigation öffnen" title="Navigation öffnen" hidden><i class="fa-solid fa-bars" aria-hidden="true"></i></button><?php endif; ?><a class="hub-brand" href="/" aria-label="HomeHub Startseite"><span class="hub-brand-mark">H</span><span class="hub-brand-copy"><strong><?= h((string) $config['name']) ?></strong><small><?= h((string) $config['tagline']) ?></small></span></a><nav class="hub-header-context hub-breadcrumbs" aria-label="Brotkrumennavigation"><?php foreach (adminBreadcrumbs($showMedia, $showUsers, $loggedIn, $showTags) as $i => $crumb): ?><?php if ($i > 0): ?><span class="hub-crumb-separator" aria-hidden="true">›</span><?php endif; ?><?php if ($crumb['href'] !== null): ?><a href="<?= h($crumb['href']) ?>"><?= h($crumb['label']) ?></a><?php else: ?><span aria-current="page"><?= h($crumb['label']) ?></span><?php endif; ?><?php endforeach; ?></nav>
<div class="hub-header-actions"><a class="admin-btn link-button" href="/" aria-label="Zum Wiki" title="Zum Wiki"><i class="fa-solid fa-book-open" aria-hidden="true"></i><span>Zum Wiki</span></a><?php require __DIR__ . '/theme-toggle-view.php'; ?><?php if ($loggedIn): ?><form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="logout"><button class="admin-btn link-button" aria-label="Abmelden" title="Abmelden"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Abmelden</span></button></form><?php endif; ?></div></header>
<?php if (!$loggedIn): ?>
<main class="login-panel"><h1>Admin-Login</h1><p>Für Medien- und Benutzerverwaltung anmelden.</p>
<?php if (!is_file(dirname(__DIR__) . '/data/config/users.json')): ?><div class="alert">Noch kein Benutzer eingerichtet. Führe auf dem Server <code>docker compose exec -u www-data homehub php bin/create-admin.php</code> aus (siehe README).</div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="login"><label>Benutzername<input type="text" name="username" autocomplete="username" required autofocus></label><label>Passwort<input type="password" name="password" autocomplete="current-password" required></label><button class="admin-btn primary"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span>Anmelden</span></button></form></main>
<?php else: ?>
<div class="admin-layout" id="admin-layout"><div class="hub-mobile-nav-backdrop" hidden></div><aside class="admin-sidebar" id="admin-sidebar"><div class="admin-nav-section"><h2>Verwaltung</h2><?php if ($canMedia): ?><a class="admin-nav-link<?= $showMedia ? ' active' : '' ?>" href="/admin.php?media=1"<?= $showMedia ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-images" aria-hidden="true"></i> Medienverwaltung</a><?php endif; ?><?php if ($isAdmin): ?><a class="admin-nav-link<?= $showUsers ? ' active' : '' ?>" href="/admin.php?users=1"<?= $showUsers ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-users" aria-hidden="true"></i> Benutzerverwaltung</a><a class="admin-nav-link<?= $showTags ? ' active' : '' ?>" href="/admin.php?tags=1"<?= $showTags ? ' aria-current="page"' : '' ?>><i class="fa-solid fa-tags" aria-hidden="true"></i> Schlagwörter</a><?php endif; ?></div><?php require __DIR__ . '/sidebar-account-view.php'; ?></aside><main class="admin-main">
<?php if ($error): ?><div class="alert error" data-hub-notice="error"><?= h($error) ?></div><?php endif; ?>
<?php if ($notice): ?><div class="alert success" data-hub-notice="<?= h($noticeType) ?>"><?= h($notice) ?></div><?php endif; ?>
<?php if ($showTags): ?>
<h1>Schlagwörter</h1>
<p>Zentralen Schlagwortkatalog verwalten.</p>
<?php require __DIR__ . '/admin-tags-view.php'; ?>
<?php elseif ($showUsers): ?>
<h1>Benutzerverwaltung</h1>
<p>Benutzerkonten und Rollen verwalten. Neue Passwörter müssen mindestens 12 Zeichen enthalten.</p>
<?php require __DIR__ . '/admin-users-view.php'; ?>
<?php elseif ($showMedia): ?>
<h1>Medienverwaltung</h1>
<p>Ordner selbst anlegen, Bilder durchsuchen und Markdown-Referenzen kopieren. Bestehende Uploads bleiben im Hauptordner erhalten.</p>
<?php
$folders = $media->folders();
$mediaFolder = (string) ($_GET['folder'] ?? '');
if (!in_array($mediaFolder, $folders, true)) $mediaFolder = '';
$children = [];
foreach ($folders as $folder) if ($folder !== '') $children[dirname($folder) === '.' ? '' : dirname($folder)][] = $folder;
$tree = function (string $parent, bool $picker = false) use (&$tree, $children, $mediaFolder): void {
    foreach ($children[$parent] ?? [] as $folder) {
        $branch = isset($children[$folder]);
        if ($branch) echo '<details' . ($folder === $mediaFolder || str_starts_with($mediaFolder, $folder . '/') ? ' open' : '') . '><summary>';
        echo $picker ? '<label class="folder-choice"><input type="radio" name="target_folder" value="' . h($folder) . '"> ' : '<a class="' . ($folder === $mediaFolder ? 'active' : '') . '" href="/admin.php?media=1&amp;folder=' . rawurlencode($folder) . '">';
        echo '<i class="fa-regular fa-folder" aria-hidden="true"></i> ' . h(basename($folder)) . ($picker ? '</label>' : '</a>');
        if ($branch) { echo '</summary><div class="folder-children">'; $tree($folder, $picker); echo '</div></details>'; }
    }
};
?>
<div class="media-manager">
<aside class="media-folder-nav"><div class="media-folder-heading"><strong>Ordner</strong><button class="admin-btn" type="button" data-open-media="folder-dialog" title="Neuer Unterordner" aria-label="Neuer Unterordner"><i class="fa-solid fa-folder-plus" aria-hidden="true"></i><span>Neu</span></button></div>
<a class="<?= $mediaFolder === '' ? 'active' : '' ?>" href="/admin.php?media=1"><i class="fa-solid fa-house"></i> Hauptordner</a>
<?php $tree(''); ?>
</aside>
<div class="media-manager-main"><form class="upload-form" method="post" enctype="multipart/form-data" id="upload-form"><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="upload"><input type="hidden" name="folder" value="<?= h($mediaFolder) ?>"><div class="media-upload-heading"><strong>Medien hochladen</strong><small><i class="fa-regular fa-folder" aria-hidden="true"></i> <?= h($mediaFolder ?: 'Hauptordner') ?></small></div>
<div class="drop-zone" id="drop-zone"><div class="upload-empty" id="upload-empty"><i class="fa-solid fa-cloud-arrow-up upload-symbol" aria-hidden="true"></i><strong>Dateien hierher ziehen</strong><span class="upload-hint">oder</span><label class="admin-btn primary upload-choose" for="media-upload-files"><i class="fa-solid fa-folder-open" aria-hidden="true"></i><span>Dateien auswählen</span></label><small>JPG, PNG, WebP, GIF, PDF, TXT · maximal 20 Dateien / 10 MB insgesamt</small></div><input id="media-upload-files" type="file" name="files[]" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.txt" multiple required>
<div class="upload-selection" id="upload-selection" hidden><strong id="upload-count" aria-live="polite"></strong><ul id="upload-file-list" class="upload-file-list"></ul></div><p id="upload-error" role="alert" hidden></p><div class="upload-footer" id="upload-actions"><button class="admin-btn danger" type="button" id="upload-clear" hidden><i class="fa-solid fa-trash" aria-hidden="true"></i><span>Auswahl löschen</span></button><button class="admin-btn primary" type="submit" id="upload-submit"><i class="fa-solid fa-upload" aria-hidden="true"></i><span>Hochladen</span></button></div></div></form>
<nav class="media-crumbs" aria-label="Medienordner"><a href="/admin.php?media=1">Hauptordner</a><?php $prefix = ''; foreach ($mediaFolder === '' ? [] : explode('/', $mediaFolder) as $part): $prefix = ltrim($prefix . '/' . $part, '/'); ?> › <a href="/admin.php?media=1&amp;folder=<?= rawurlencode($prefix) ?>"><?= h($part) ?></a><?php endforeach; ?></nav>
<div class="media-bulk-toolbar"><button class="admin-btn" type="button" id="media-select-all"><i class="fa-solid fa-check-double" aria-hidden="true"></i><span>Alle sichtbaren auswählen</span></button><button class="admin-btn" type="button" id="media-clear"><i class="fa-solid fa-square-xmark" aria-hidden="true"></i><span>Auswahl aufheben</span></button><span id="media-count" aria-live="polite">0 ausgewählt</span><button class="admin-btn" type="button" id="media-move-selected" data-open-media="move-dialog" disabled><i class="fa-solid fa-folder-open" aria-hidden="true"></i><span>Verschieben</span></button><button type="button" id="media-delete-selected" class="admin-btn danger" disabled><i class="fa-solid fa-trash-can" aria-hidden="true"></i><span>Löschen</span></button></div>
<input id="media-filter" type="search" placeholder="Dateiname suchen ..." aria-label="Medien suchen">
<div class="media-grid" id="media-manager-grid">
<?php foreach ($media->all($mediaFolder) as $asset): $asset['url'] = '/media.php?id=' . $mediaRegistry->register($asset['path']); ?>
<article class="media-card" data-media-name="<?= h(strtolower($asset['name'])) ?>"><label class="media-select"><input type="checkbox" value="<?= h($asset['path']) ?>" aria-label="<?= h($asset['name']) ?> auswählen"> Auswählen</label><div class="media-thumb"><?php if ($asset['image']): ?><img src="<?= h($asset['url']) ?>" alt="" loading="lazy"><?php else: ?><span>📄</span><?php endif; ?></div><strong title="<?= h($asset['name']) ?>"><?= h($asset['name']) ?></strong><small><?= number_format($asset['size'] / 1024, 1, ',', '.') ?> KB</small><div class="media-actions"><button type="button" class="admin-btn copy-markdown" data-markdown="<?= h(($asset['image'] ? '![' : '[') . $asset['name'] . '](' . $asset['url'] . ')') ?>"><i class="fa-solid fa-copy" aria-hidden="true"></i><span>Markdown kopieren</span></button><button type="button" class="admin-btn" data-open-media="rename-dialog" data-media-path="<?= h($asset['path']) ?>" data-media-filename="<?= h($asset['name']) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Umbenennen</span></button></div></article>
<?php endforeach; ?></div></div></div>
<dialog id="rename-dialog" class="media-dialog" aria-labelledby="rename-dialog-title"><form method="post"><h2 class="hub-dialog-header" id="rename-dialog-title">Medium umbenennen</h2><p>Aktueller Dateiname: <strong data-current-name></strong></p><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="move-media"><input type="hidden" name="file"><input type="hidden" name="target_folder" value="<?= h($mediaFolder) ?>"><label>Neuer Dateiname<input name="target_name" required maxlength="150" pattern="[a-zA-Z0-9][a-zA-Z0-9._-]*" autofocus></label><p>Bestehende ID-Verlinkungen bleiben erhalten.</p><div class="media-dialog-actions"><button class="admin-btn" type="button" data-close-media><i class="fa-solid fa-xmark" aria-hidden="true"></i><span>Abbrechen</span></button><button class="admin-btn primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span>Speichern</span></button></div></form></dialog>
<dialog id="folder-dialog" class="media-dialog" aria-labelledby="folder-dialog-title"><form method="post"><h2 class="hub-dialog-header" id="folder-dialog-title">Neuer Ordner</h2><p>In: <?= h($mediaFolder ?: 'Hauptordner') ?></p><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="create-media-folder"><input type="hidden" name="folder" value="<?= h($mediaFolder) ?>"><label>Ordnername<input name="folder_name" required maxlength="150" pattern="[a-zA-Z0-9][a-zA-Z0-9._-]*" autofocus></label><div class="media-dialog-actions"><button class="admin-btn" type="button" data-close-media><i class="fa-solid fa-xmark" aria-hidden="true"></i><span>Abbrechen</span></button><button class="admin-btn primary"><i class="fa-solid fa-folder-plus" aria-hidden="true"></i><span>Erstellen</span></button></div></form></dialog>
<dialog id="move-dialog" class="media-dialog" aria-labelledby="move-dialog-title"><form method="post" id="media-bulk-form"><h2 class="hub-dialog-header" id="move-dialog-title">Medien verschieben</h2><input type="hidden" name="csrf" value="<?= h(csrf()) ?>"><input type="hidden" name="action" value="bulk-media"><input type="hidden" name="operation" value="move"><input type="hidden" name="folder" value="<?= h($mediaFolder) ?>"><div id="media-bulk-files"><input type="hidden" name="selection" value="[]"></div><div class="media-target-tree"><label class="folder-choice"><input type="radio" name="target_folder" value="" required> <i class="fa-solid fa-house" aria-hidden="true"></i> Hauptordner</label><?php $tree('', true); ?></div><p id="media-target-preview"></p><div class="media-dialog-actions"><button class="admin-btn" type="button" data-close-media><i class="fa-solid fa-xmark" aria-hidden="true"></i><span>Abbrechen</span></button><button class="admin-btn primary" id="media-move-submit" disabled><i class="fa-solid fa-folder-open" aria-hidden="true"></i><span>Verschieben</span></button></div></form></dialog>
<script src="/js/admin-media.js?v=6" defer></script>
<?php else: ?><h1>HomeHub-Verwaltung</h1><p>Medien und Benutzer verwaltest du hier. Seiten erstellst und bearbeitest du direkt im <a href="/">Wiki</a>.</p><?php endif; ?>
<footer class="footer hub-footer">HomeHub CMS <span>·</span> © 2026 M10-Solutions · <a href="https://www.gnu.org/licenses/gpl-3.0.html" rel="noreferrer">GNU GPLv3+</a></footer>
</main></div><?php endif; ?>
<?php if (!$loggedIn): ?><footer class="footer hub-footer hub-login-footer">HomeHub CMS <span>·</span> © 2026 M10-Solutions · <a href="https://www.gnu.org/licenses/gpl-3.0.html" rel="noreferrer">GNU GPLv3+</a></footer><?php endif; ?>
</body></html>
