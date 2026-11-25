<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

// Dependency-free smoke test for ID generation and validation.
require dirname(__DIR__) . '/src/lib/PageIdentity.php';
use HomeHub\PageIdentity;
$a = PageIdentity::generate();
$b = PageIdentity::generate();
if (!PageIdentity::valid($a) || !PageIdentity::valid($b) || $a === $b) throw new RuntimeException('Page ID generation failed');
foreach (['p_123', 'p_' . str_repeat('A', 32), '../x', ''] as $bad) {
    if (PageIdentity::valid($bad)) throw new RuntimeException('Invalid ID accepted');
}
echo "OK: page ID generation and validation\n";
