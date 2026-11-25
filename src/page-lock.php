<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
use HomeHub\{Admin, JSONAuthenticator, MediaOperationLock, PageEditLease, PageRepository, PermissionService, Session};
require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/config/app.php';
Session::start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function lockReply(int $status, array $body): never { http_response_code($status); echo json_encode($body, JSON_UNESCAPED_UNICODE); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') lockReply(405, ['error'=>'Methode nicht erlaubt.']);
if (!isset($_SESSION['csrf']) || !hash_equals((string) $_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) lockReply(403, ['error'=>'Ungültiges Sicherheitstoken.']);
$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
$expectedOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
if ($origin !== '' && !hash_equals($expectedOrigin, $origin)) lockReply(403, ['error'=>'Ungültiger Ursprung.']);
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
$role = $auth->currentRole();
$user = $auth->currentUserId() ?? '';
if (!PermissionService::can($role, 'page.edit') || $user === '') lockReply(403, ['error'=>'Keine Bearbeitungsberechtigung.']);
$slug = (string) ($_POST['slug'] ?? '');
$action = (string) ($_POST['action'] ?? '');
$token = (string) ($_POST['lock_token'] ?? '');
try {
    if (!Admin::slugIsValid($slug)) throw new RuntimeException('Ungültige Seite.');
    $repo = new PageRepository($config['content_path']);
    $page = $repo->findForAccess($slug);
    if ($page === null || !($page->published ? PermissionService::canReadPage($page, $role, $repo, $user) : PermissionService::canReadDraft($page, $role, $repo, $user))) lockReply(403, ['error'=>'Kein Zugriff auf diese Seite.']);
    $result = (new MediaOperationLock(dirname(__DIR__) . '/data/config'))->run(static function () use ($slug, $user, $token, $action): array {
        $lease = new PageEditLease(dirname(__DIR__) . '/data/config');
        if ($action === 'acquire') return ['lock_token'=>$lease->acquire($slug, $user), 'ttl'=>PageEditLease::TTL];
        if ($action === 'heartbeat') { $lease->heartbeat($slug, $user, $token); return ['ok'=>true]; }
        if ($action === 'release') { $lease->release($slug, $user, $token); return ['ok'=>true]; }
        throw new RuntimeException('Unbekannte Aktion.');
    });
    lockReply(200, $result);
} catch (Throwable $e) { lockReply(409, ['error'=>$e->getMessage()]); }
