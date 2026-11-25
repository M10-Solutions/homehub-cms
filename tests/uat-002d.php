<?php
/* Copyright (C) 2026 M10-Solutions
 * SPDX-License-Identifier: GPL-3.0-or-later */
$root = dirname(__DIR__);
$checks = ['src/js/admin-media.js' => ['new DataTransfer()', 'input.files = transfer.files', 'renameForm.elements.file.value'], 'src/admin.php' => ['id="rename-dialog"', 'data-media-path=', 'name="action" value="move-media"'], 'src/js/page-link-feedback.js' => ["method:'HEAD'", 'response.status === 401 || response.status === 403', 'location.assign(url.href)'], 'src/index.php' => ['page-link-feedback.js']];
foreach ($checks as $file => $needles) foreach ($needles as $needle) if (!str_contains(file_get_contents($root.'/'.$file), $needle)) { fwrite(STDERR, "Missing $needle in $file\n"); exit(1); }
echo "UAT-002d structure OK\n";
