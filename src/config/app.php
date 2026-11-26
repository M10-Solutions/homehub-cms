<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

return [
    'name' => 'HomeHub',
    // Release version: major.minor-YYYYMMDD (last revision date).
    'version' => '1.1-20261126',
    'tagline' => 'Dein Zuhause auf einen Blick.',
    // Content stays outside the public web root.
    'content_path' => dirname(__DIR__, 2) . '/data/content',
    'home_page' => 'startseite',
    'timezone' => in_array(getenv('TZ'), timezone_identifiers_list(), true) ? getenv('TZ') : 'Europe/Zurich',
];
