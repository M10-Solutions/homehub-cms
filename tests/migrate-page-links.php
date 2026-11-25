<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */

declare(strict_types=1);

/** Focused regression checks for PAGE-003 link formats. */
$script = file_get_contents(dirname(__DIR__) . '/bin/migrate-page-links.php');
if ($script === false) throw new RuntimeException('Migration script unavailable');
$start = strpos($script, '/** Normalize a page target');
$end = strpos($script, "\ntry {", $start);
if ($start === false || $end === false) throw new RuntimeException('Cannot locate migration functions');
eval(substr($script, $start, $end - $start));
$slugs = [
    'smart-home/beleuchtung' => 'p_' . str_repeat('a', 32),
    'smart-home/storen-beschattung' => 'p_' . str_repeat('b', 32),
    'dokumentation/ubuntu-home-server/virtualisierung/einfuhrung' => 'p_' . str_repeat('c', 32),
];
$input = <<<'MD'
---
title: "[Not a link](beleuchtung.md)"
---

- [Licht](beleuchtung.md)
- [Storen](storen-beschattung.md)
- [Einführung](/dokumentation/ubuntu-home-server/virtualisierung/einfuhrung)
- [Unbekannt](fehlt.md)
- [Web](https://example.org/test)
- ![Bild](beleuchtung.md)
- `[Code](beleuchtung.md)`

```
[Code](beleuchtung.md)
```
MD;
$stats = ['internal' => [], 'warnings' => []];
$out = migrateLinks($input, $slugs, 'smart-home/start.md', $stats);
foreach (['p_' . str_repeat('a', 32), 'p_' . str_repeat('b', 32), 'p_' . str_repeat('c', 32)] as $id) {
    if (!str_contains($out, '/page.php?id=' . $id)) throw new RuntimeException('Missing migrated ID ' . $id);
}
if (count($stats['internal']) !== 3 || count($stats['warnings']) !== 1) throw new RuntimeException('Unexpected counts: ' . json_encode($stats));
foreach (['![Bild](beleuchtung.md)', '`[Code](beleuchtung.md)`', '[Web](https://example.org/test)', 'title: "[Not a link](beleuchtung.md)"'] as $untouched) {
    if (!str_contains($out, $untouched)) throw new RuntimeException('Changed protected content: ' . $untouched);
}
$again = ['internal' => [], 'warnings' => []];
if (migrateLinks($out, $slugs, 'smart-home/start.md', $again) !== $out) throw new RuntimeException('Not idempotent');
echo "PAGE-003 migration tests passed.\n";
