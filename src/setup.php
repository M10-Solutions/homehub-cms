<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

use HomeHub\JSONAuthenticator;
use HomeHub\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
Session::start();
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
$auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
if (!$auth->needsSetup()) { header('Location: /', true, 303); exit; }
$csrf = $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $expectedOrigin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? '')) || ($origin !== '' && !hash_equals($origin, $expectedOrigin))) {
        http_response_code(403);
        $error = 'Sicherheitsprüfung fehlgeschlagen. Bitte die Seite neu laden.';
    } elseif ((string) ($_POST['password'] ?? '') !== (string) ($_POST['password_confirm'] ?? '')) {
        $error = 'Die Passwörter stimmen nicht überein.';
    } else {
        try {
            $auth->registerUser((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''), 'admin', initialOnly: true);
            $_SESSION['setup_complete'] = true;
            header('Location: /?setup=complete', true, 303);
            exit;
        } catch (\InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            if (!$auth->needsSetup()) { header('Location: /', true, 303); exit; }
            $error = 'Die Einrichtung konnte nicht abgeschlossen werden. Bitte erneut versuchen oder das Serverprotokoll prüfen.';
            error_log('HomeHub setup: ' . $e->getMessage());
        }
    }
}
function setupEscape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>HomeHub CMS – Ersteinrichtung</title><link rel="stylesheet" href="/css/setup.css?v=1"></head>
<body><main class="setup-card">
<div class="setup-brand">⌂ HomeHub CMS</div>
<h1>Willkommen bei HomeHub</h1>
<p class="setup-description">Erstelle dein Administratorkonto, um HomeHub einzurichten.</p>
<?php if ($error !== ''): ?><p class="setup-error" role="alert"><?= setupEscape($error) ?></p><?php endif; ?>
<form method="post" action="/setup.php">
<input type="hidden" name="csrf" value="<?= setupEscape($csrf) ?>">
<label for="username">Administrator-Benutzername</label>
<input id="username" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]{3,50}" autocomplete="username" value="<?= setupEscape((string) ($_POST['username'] ?? 'admin')) ?>">
<label for="password">Passwort (mindestens 12 Zeichen)</label>
<input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
<label for="password_confirm">Passwort bestätigen</label>
<input id="password_confirm" name="password_confirm" type="password" minlength="12" required autocomplete="new-password">
<p class="setup-note">Die Ersteinrichtung ist nur einmal möglich. Danach meldest du dich mit diesem Konto an.</p>
<button type="submit">HomeHub einrichten</button>
</form></main></body></html>
