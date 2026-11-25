<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
declare(strict_types=1);

use HomeHub\JSONAuthenticator;

if (PHP_SAPI !== 'cli') { http_response_code(403); exit(1); }
require dirname(__DIR__) . '/vendor/autoload.php';
if (!stream_isatty(STDIN)) { fwrite(STDERR, "Bitte interaktiv im Container starten.\n"); exit(1); }
try {
    $auth = new JSONAuthenticator(dirname(__DIR__) . '/data/config');
    if ($auth->allUsers() !== []) throw new RuntimeException('Benutzerdatei enthält bereits Konten. Keine Änderungen vorgenommen.');
    fwrite(STDOUT, 'Admin-Benutzername: ');
    $name = trim((string) fgets(STDIN));
    fwrite(STDOUT, 'Passwort (mind. 12 Zeichen; Eingabe sichtbar): ');
    $password = rtrim((string) fgets(STDIN), "\r\n");
    fwrite(STDOUT, 'Passwort wiederholen: ');
    if ($password !== rtrim((string) fgets(STDIN), "\r\n")) throw new RuntimeException('Passwörter stimmen nicht überein.');
    $auth->registerUser($name, $password, 'admin', initialOnly: true);
    echo "Administrator angelegt.\n";
} catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
