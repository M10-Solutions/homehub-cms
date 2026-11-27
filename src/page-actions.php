<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\Admin;
use HomeHub\JSONAuthenticator;
use HomeHub\PermissionService;
use HomeHub\PageRepository;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/config/app.php';
date_default_timezone_set($config['timezone']);
Session::start();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit;
}
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
if (!PermissionService::can($auth->currentRole(), 'page.create')) {
    http_response_code(403); exit('Keine Berechtigung.');
}
$csrf = (string) ($_SESSION['csrf'] ?? '');
if ($csrf === '' || !hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
    http_response_code(403); exit('Ungültiges Sicherheitstoken.');
}
$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
$expected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
if ($origin !== '' && !hash_equals($expected, $origin)) {
    http_response_code(403); exit('Ungültiger Ursprung.');
}
$admin = new Admin($config['content_path'], dirname(__DIR__) . '/data/config');
$action = (string) ($_POST['page_action'] ?? '');
$role = $auth->currentRole();
$userId = $auth->currentUserId();
$repository = new PageRepository($config['content_path']);
$assertManage = static function (string $pageSlug) use ($repository, $role, $userId): void {
    $page = $repository->findForAccess($pageSlug);
    if ($page === null || !PermissionService::canManagePage($page, $role, $repository, $userId)) {
        throw new RuntimeException('Keine Berechtigung für diese Seite.');
    }
};
$slug = trim((string) ($_POST['slug'] ?? ''), '/');
$json = in_array($action, ['create', 'check-create'], true) && ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
$reply = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
};
try {
    if (!Admin::slugIsValid($slug)) throw new RuntimeException('Ungültiger Seitenpfad.');
    if (in_array($action, ['create', 'check-create'], true)) {
        if (!PermissionService::canCreateAt($slug, $role, $repository, $userId)) {
            throw new RuntimeException('Neue Seiten sind nur in berechtigten Kapiteln möglich.');
        }
        if ($action === 'check-create') {
            $admin->assertCreateAvailable($slug);
            $reply(['ok' => true]);
            exit;
        }
        $input = [
            'title' => $_POST['title'] ?? '', 'description' => $_POST['description'] ?? '',
            'body' => '', 'labels' => '', 'order' => 100, 'published' => $role === 'admin',
            'nav' => true, 'visibility' => 'all', 'allowed_roles' => [], 'allowed_users' => [],
        ];
        $admin->save($slug, $input, true);
        $target = $slug;
    } elseif ($action === 'settings') {
        $assertManage($slug);
        $existing = $admin->read($slug);
        $revision = (string) ($_POST['revision'] ?? '');
        if ($revision === '' || !hash_equals(hash('sha256', json_encode($existing, JSON_THROW_ON_ERROR)), $revision)) {
            throw new RuntimeException('Die Seite wurde zwischenzeitlich geändert. Bitte neu laden.');
        }
        $input = $existing;
        foreach ($role === 'admin' ? ['order', 'visibility'] : ['order'] as $key) {
            $input[$key] = $_POST[$key] ?? '';
        }
        $input['published'] = isset($_POST['published']);
        $input['nav'] = isset($_POST['nav']);
        if ($role === 'admin') {
            $input['allowed_roles'] = $input['visibility'] === 'roles' ? ($_POST['allowed_roles'] ?? []) : [];
            $input['allowed_users'] = $input['visibility'] === 'users' ? ($_POST['allowed_users'] ?? []) : [];
        }
        $admin->save($slug, $input, false, $slug, false, $revision);
        $target = $slug;
    } elseif ($action === 'move') {
        $assertManage($slug);
        $to = trim((string) ($_POST['target_slug'] ?? ''), '/');
        if (!Admin::slugIsValid($to)) throw new RuntimeException('Ungültiger Zielpfad.');
        if (!PermissionService::canCreateAt($to, $role, $repository, $userId)) throw new RuntimeException('Keine Berechtigung für das Zielkapitel.');
        $revision = (string) ($_POST['revision'] ?? '');
        if ($revision === '') throw new RuntimeException('Bitte die Seite neu laden.');
        $admin->move($slug, $to, $revision);
        $target = $to;
    } elseif ($action === 'delete') {
        $assertManage($slug);
        $tree = ($_POST['delete_scope'] ?? '') === 'tree';
        // Removing a restrictive ancestor alone would broaden inherited access.
        (new \HomeHub\PageTrash($config['content_path'], dirname(__DIR__) . '/data/config'))->remove($slug, $tree, $auth->currentUser(), $assertManage, static function () use ($slug, $tree, $config): void {
            if ($tree) return;
            $fresh = new PageRepository($config['content_path']);
            $page = $fresh->findForAccess($slug);
            if ($page !== null && $page->visibility !== 'all') {
                foreach ($fresh->allIncludingDrafts() as $child) {
                    if (str_starts_with($child->slug, $slug . '/')) throw new RuntimeException('Geschütztes Kapitel mit Unterseiten kann nur als gesamte Struktur gelöscht werden.');
                }
            }
        });
        $_SESSION['hub_page_deleted_notice'] = 'In den Papierkorb verschoben.';
        $target = '';
    } else {
        throw new RuntimeException('Unbekannte Aktion.');
    }
    $url = $target === '' ? '/' : '/' . implode('/', array_map('rawurlencode', explode('/', $target)));
    if ($target !== '' && in_array($action, ['create', 'settings', 'move'], true) && !$admin->read($target)['published']) {
        $url = '/?draft=' . rawurlencode($target);
    }
    if ($json) { $reply(['ok' => true, 'url' => $url]); exit; }
    header('Location: ' . $url, true, 303);
    exit;
} catch (Throwable $error) {
    if ($json || $action === 'check-create') { $reply(['ok' => false, 'error' => $error->getMessage()], 400); exit; }
    http_response_code(400);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="de"><meta charset="utf-8"><title>HomeHub – Aktion fehlgeschlagen</title><main style="font:1rem system-ui;max-width:42rem;margin:4rem auto"><h1>Aktion fehlgeschlagen</h1><p>'
        . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</p><p><a href="/">Zurück zum Wiki</a></p></main></html>';
}
