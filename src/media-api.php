<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
\HomeHub\Session::start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
function reply(int $status, array $payload): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); exit; }
try {
    $auth = new \HomeHub\JSONAuthenticator(dirname(__DIR__) . '/data/config');
    if (!$auth->isSigned() || !\HomeHub\PermissionService::can($auth->currentRole(), 'media.read')) reply(403, ['error'=>'Keine Berechtigung für Medienverwaltung.']);
    $media = new \HomeHub\MediaLibrary(dirname(__DIR__) . '/data/upload');
    $registry = new \HomeHub\MediaRegistry(dirname(__DIR__) . '/data/config');
    $decorate = static function (array $items) use ($registry): array {
        foreach ($items as &$item) {
            $item['id'] = $registry->register($item['path']);
            $item['url'] = '/media.php?id=' . $item['id'];
        }
        unset($item);
        return $items;
    };
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $folder = (string) ($_GET['folder'] ?? '');
        reply(200, ['folders'=>$media->folders(), 'items'=>$decorate($media->all($folder))]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, ['error'=>'Methode nicht erlaubt.']);
    $token = (string) ($_POST['csrf'] ?? '');
    if (!isset($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $token)) reply(403, ['error'=>'Ungültiges Sicherheitstoken.']);
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '') {
        $expected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
        if (!hash_equals($expected, $origin)) reply(403, ['error'=>'Ungültiger Ursprung.']);
    }
    $action = (string) ($_POST['action'] ?? '');
    $required = $action === 'upload' ? 'media.upload' : 'media.manage';
    if (!\HomeHub\PermissionService::can($auth->currentRole(), $required)) reply(403, ['error'=>'Keine Berechtigung.']);
    if ($action === 'folder') $media->createFolder((string) ($_POST['folder'] ?? ''));
    elseif ($action === 'upload') {
        $uploaded = $media->upload($_FILES['file'] ?? [], (string) ($_POST['folder'] ?? ''));
        $registry->register($uploaded);
    }
    elseif ($action === 'move') {
        (new \HomeHub\MediaMover($media, $registry, new \HomeHub\MediaOperationLock(dirname(__DIR__) . '/data/config')))
            ->move((string) ($_POST['file'] ?? ''), (string) ($_POST['target_folder'] ?? ''), (string) ($_POST['target_name'] ?? ''));
    }
    elseif ($action === 'delete') {
        $path = (string) ($_POST['file'] ?? '');
        (new \HomeHub\MediaDeleter($media, $registry, new \HomeHub\MediaOperationLock(dirname(__DIR__) . '/data/config')))->delete($path);
    }
    else reply(400, ['error'=>'Unbekannte Aktion.']);
    $folder = (string) ($_POST['current_folder'] ?? $_POST['folder'] ?? '');
    reply(200, ['folders'=>$media->folders(), 'items'=>$decorate($media->all($folder))]);
} catch (\Throwable $e) { reply(400, ['error'=>$e->getMessage()]); }
