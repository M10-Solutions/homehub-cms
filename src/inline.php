<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\Admin;
use HomeHub\JSONAuthenticator;
use HomeHub\PageRepository;
use HomeHub\PermissionService;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = require __DIR__ . '/config/app.php';
Session::start();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json; charset=utf-8');
function inlineReply(int $code, array $data): never { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') inlineReply(405, ['error'=>'Methode nicht erlaubt.']);
$token = (string) ($_POST['csrf'] ?? '');
if (!isset($_SESSION['csrf']) || !hash_equals((string) $_SESSION['csrf'], $token)) inlineReply(403, ['error'=>'Sicherheitstoken ungültig. Bitte Seite neu laden.']);
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    $expected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
    if (!hash_equals($expected, $origin)) inlineReply(403, ['error'=>'Ungültiger Ursprung.']);
}
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
$role = $auth->currentRole();
if (!PermissionService::can($role, 'page.edit')) inlineReply(403, ['error'=>'Keine Bearbeitungsberechtigung.']);
$slug = (string) ($_POST['slug'] ?? '');
$repo = new PageRepository($config['content_path']);
$page = $repo->findForAccess($slug);
if ($page === null || !($page->published ? PermissionService::canReadPage($page, $role, $repo, $auth->currentUserId()) : PermissionService::canReadDraft($page, $role, $repo, $auth->currentUserId()))) inlineReply(403, ['error'=>'Seite nicht verfügbar oder nicht freigegeben.']);
$body = (string) ($_POST['body'] ?? '');
if (strlen($body) > 1048576) inlineReply(413, ['error'=>'Inhalt ist zu groß.']);
$action = (string) ($_POST['action'] ?? '');
try {
    $admin = new Admin($config['content_path'], dirname(__DIR__) . '/data/config');
    if ($action === 'preview') inlineReply(200, ['html'=>$admin->preview($body)]);
    if ($action !== 'save') inlineReply(400, ['error'=>'Unbekannte Aktion.']);
    $revision = (string) ($_POST['revision'] ?? '');
    $lockToken = (string) ($_POST['lock_token'] ?? '');
    if ($revision === '' || $lockToken === '') inlineReply(409, ['error'=>'Bearbeitungssperre oder Revision fehlt.']);
    // Content-only save preserves all existing front-matter metadata and applies access checks again.
    $admin->save($slug, ['body'=>$body], false, $slug, true, $revision, $auth->currentUserId() ?? '', $lockToken);
    inlineReply(200, ['ok'=>true, 'revision'=>hash('sha256', $body)]);
} catch (Throwable $e) { inlineReply(409, ['error'=>$e->getMessage()]); }
